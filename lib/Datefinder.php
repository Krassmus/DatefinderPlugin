<?php

require_once 'lib/classes/QuestionType.interface.php';

class Datefinder extends QuestionnaireQuestion implements QuestionType
{
    static public function getIcon($active = false, $add = false)
    {
        return Icon::create(($add ?  "add/" : "")."date", $active ? "clickable" : "info");
    }

    static public function getName()
    {
        return _("Terminfindung");
    }

    public function getEditingTemplate()
    {
        $tf = new Flexi_TemplateFactory(realpath(__DIR__."/../views"));
        $template = $tf->open("datefinder/datefinder_edit.php");
        $template->set_attribute('vote', $this);
        return $template;
    }

    public function createDataFromRequest()
    {
        $questions = Request::getArray("questions");
        $question_data = $questions[$this->getId()];

        $dates = array();
        foreach ($question_data['questiondata']['day'] as $key => $date) {
            if (trim($date)) {
                $dates[] = strtotime($date . " " . $question_data['questiondata']['time'][$key]);
            }
        }
        sort($dates);
        unset($question_data['questiondata']['day']);
        unset($question_data['questiondata']['time']);
        $question_data['questiondata']['dates'] = $dates;

        $this->setData($question_data);
    }

    public function getDisplayTemplate()
    {
        $tf = new Flexi_TemplateFactory(realpath(__DIR__."/../views"));
        $template = $tf->open("datefinder/datefinder_answer.php");
        $template->set_attribute('vote', $this);
        return $template;
    }

    public function createAnswer()
    {
        $answer = $this->getMyAnswer();
        $answers = Request::getArray("answers");
        $answer_data = $answers[$this->getId()];
        $answer->setData($answer_data);
        return $answer;
    }

    public function getResultTemplate($only_user_ids = null)
    {
        if ($this['questiondata']['status'] === "needsmanualevaluation"
                && $this->questionnaire->isStopped()) {
            $this->onEnding();
        } else {
            $this->alterDynamicAnswers();
        }
        $tf = new Flexi_TemplateFactory(realpath(__DIR__."/../views"));
        $template = $tf->open("datefinder/datefinder_evaluation.php");
        $template->set_attribute('vote', $this);
        return $template;
    }

    public function getResultArray()
    {

    }

    public function getConflictingSchedules($from, $to, $user_id = null)
    {
        $user_id || $user_id = $GLOBALS['user']->id;
        if ($from > $to) {
            $i = $from;
            $from = $to;
            $to = $i;
        }

        $calendar_events = DBManager::get()->prepare("
            SELECT event_data.summary AS title, event_data.start, event_data.end, event_data.event_id AS id, calendar_event.range_id, 'user' as rangetype
            FROM event_data
                LEFT JOIN calendar_event ON (event_data.event_id = calendar_event.event_id)
            WHERE calendar_event.range_id = :user_id
                AND (
                    (event_data.end > :start AND event_data.end < :end)
                    OR (event_data.start > :start AND event_data.start < :end)
                    OR (event_data.start < :start AND event_data.end > :end)
                )
            UNION SELECT seminare.name AS title, termine.date AS start, termine.end_time AS end, termine.termin_id AS id, seminar_user.Seminar_id AS range_id, 'course' as rangetype
            FROM termine
                INNER JOIN seminar_user ON (seminar_user.Seminar_id = termine.range_id)
                INNER JOIN seminare ON (seminare.Seminar_id = seminar_user.Seminar_id)
            WHERE seminar_user.user_id = :user_id
                AND (
                    (termine.end_time >= :start AND termine.end_time <= :end)
                    OR (termine.date >= :start AND termine.date <= :end)
                    OR (termine.date <= :start AND termine.end_time >= :end)
                )
        ");
        $calendar_events->execute(array(
            'user_id' => $user_id,
            'start' => $from,
            'end' => $to
        ));
        $events = $calendar_events->fetchAll(PDO::FETCH_ASSOC);
        return $events;
    }

    public function onEnding()
    {
        $this->alterDynamicAnswers();

        $data = $this['questiondata']->getArrayCopy();
        $results = array();
        $results_users = array();
        foreach ($data['dates'] as $date) {
            $results[$date] = 0;
            $results_users[$date] = array();
        }
        foreach ($this->answers as $answer) {
            $dates = $answer['answerdata']['mode'] === "dynamic"
                ? $answer['answerdata']['filtereddates']
                : $answer['answerdata']['dates'] ;
            foreach ($dates as $date) {
                $results[$date]++;
                $results_users[$date][] = $answer['user_id'];
            }
        }
        $best = null;
        $simply_the_best = false;
        foreach ($results as $date => $count) {
            if ($count > 0) {
                if ($best !== null) {
                    if ($count > $results[$best]) {
                        $best = $date;
                        $simply_the_best = true;
                    } elseif($count == $results[$best]) {
                        $simply_the_best = false;
                    }
                } else {
                    $best = $date;
                    $simply_the_best = true;
                }
            }
        }
        if ($simply_the_best && $this['questiondata']['automatic']) {
            //Erstelle den Termin und/oder benachrichtige die Teilnehmer
            $this->insertDateIntoCalendars($best);
        } else {
            //Benachrichtige den Master, dass er die Auswertung vornehmen soll:
            $this['questiondata']['status'] = "needsmanualevaluation";
            $success = $this->store();
        }
    }

    public function insertDateIntoCalendars($date)
    {
        foreach ($this->answers as $answer) {
            $answerdata = $answer['answerdata']->getArrayCopy();
            $fitting_dates = $answerdata['mode'] === "dynamic"
                ? $answerdata['dates']
                : $answerdata['filtereddates'];
            if (in_array($date, $fitting_dates)) {
                $event_data = new EventData();
                $event_data['start'] = $date;
                $event_data['end'] = $date + $this['questiondata']['duration'] * 60 * 60;
                $event_data['author_id'] = $this->questionnaire->user_id;
                $event_data['editor_id'] = $this->questionnaire->user_id;
                $event_data['summary'] = $this->questionnaire->title;
                $event_data['description'] = $this['questiondata']['question'];
                $event_data['category_intern'] = 1;
                $event_data->store();

                $event = new CalendarEvent();
                $event['range_id'] = $answer['user_id'];
                $event['event_id'] = $event_data->getId();
                $event->store();
                PersonalNotifications::add(
                    $answer['user_id'],
                    "dispatch.php/questionnaire/evaluate/".$this['questionnaire_id'],
                    _("Es wurde ein Termin gefunden, der auch Ihnen passt. Er steht schon in Ihrem Terminkalender."),
                    "",
                    Icon::create("date", "clickable")->asImagePath()
                );
            } else {
                //Die Person kann nicht:
                PersonalNotifications::add(
                    $answer['user_id'],
                    "dispatch.php/questionnaire/evaluate/".$this['questionnaire_id'],
                    _("Leider wurde ein Termin bestimmt, der Ihnen nicht passt."),
                    "",
                    Icon::create("date", "inactive")->asImagePath()
                );
            }
        }
        $this['questiondata']['founddate'] = $date;
        $this['questiondata']['status'] = "founddate";
        $this->store();
    }

    private function alterDynamicAnswers()
    {
        foreach ($this->answers as $answer) {
            if ($answer['answerdata']['mode'] === "dynamic") {
                $answerdata = $answer['answerdata']->getArrayCopy();
                $filtereddates = array();
                foreach ($answerdata['dates'] as $date) {
                    $conflicts = $this->getConflictingSchedules($date, $date + $this['questiondata']['duration'] * 60 * 60, $answer['user_id']);
                    if (count($conflicts) === 0) {
                        $filtereddates[] = $date;
                    }
                }
                $answerdata['filtereddates'] = $filtereddates;
                $answer['answerdata'] = $answerdata;
                $answer['chdate'] = time();
                $answer->store();
            }
        }
    }
}
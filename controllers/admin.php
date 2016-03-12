<?php

require_once 'app/controllers/plugin_controller.php';

class AdminController extends PluginController {

    public function choose_date_action($question_id, $date)
    {
        $this->question = new Datefinder($question_id);
        if (!$this->question->questionnaire->isEditable()) {
            throw new AccessDeniedException();
        }
        $this->question->insertDateIntoCalendars($date);
        $this->redirect(URLHelper::getURL("dispatch.php/questionnaire/evaluate/".$this->question->questionnaire->getId()));
    }


}
<?
$etask = $vote->etask;
$answer = $vote->getMyAnswer();
$answerdata = $answer['answerdata'] ? $answer['answerdata']->getArrayCopy() : array();
?>

<h3>
    <?= formatReady($etask->description) ?>
</h3>

<div style="max-height: none; opacity: 1;">
    <strong><?= _("Dauer") ?></strong>: <?= htmlReady($etask->task['duration']) ?> <?= _("Stunden") ?>
</div>


<table class="default nohover">
    <thead>
        <tr>
            <th><?= _("Termin") ?></th>
            <th><?= _("Andere Termine zur gleichen Zeit") ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <? foreach ($etask->task['dates'] as $date) : ?>
            <tr>
                <td>
                    <a href="<?= URLHelper::getLink("dispatch.php/calendar/single/day", array('atime' => $date - 3 * 60 * 60)) ?>" data-dialog>
                        <?= Icon::create('date')->asImg([ 'class' => "text-bottom" ]) ?>
                        <?= date("d.m.Y H:i", $date) ?>
                    </a>
                </td>
                <td>
                    <ul class="clean">
                    <? $conflicts = $vote->getConflictingSchedules($date, $date + (60 * 60 * $etask->task['duration'])) ?>
                    <? foreach ($conflicts as $schedule) : ?>
                        <li>
                            <a href="<?= URLHelper::getLink($schedule['rangetype'] === "user"
                                ? "dispatch.php/calendar/single/edit/".$schedule['range_id']."/".$schedule['id']."?range_id=".$schedule['range_id']."&evtype=user"
                                : ($schedule['rangetype'] === "course" ? "dispatch.php/course/dates/details/".$schedule['id']."?cid=".$schedule['range_id'] : "#")) ?>"
                                data-dialog
                                title="<?= _("Hier haben Sie eigentlich schon was vor.") ?>">
                                <?= Icon::create('date+decline')->asImg([ 'class' => "text-bottom" ]) ?>
                                <strong><?= htmlReady($schedule['title']) ?></strong>:
                                <?= date("d.m.Y H:i", $schedule['start']) ?> - <?= date("d.m.Y H:i", $schedule['end']) ?>
                            </a>
                        </li>
                    <? endforeach ?>
                    </ul>
                </td>
                <td>
                    <input type="checkbox"
                           name="answers[<?= $vote->getId() ?>][answerdata][dates][]"
                           value="<?= htmlReady($date) ?>"
                           <?= ($answer->isNew() ? (!count($conflicts)) : in_array($date, (array) $answerdata['dates'])) ? " checked" : "" ?>>
                </td>
            </tr>
        <? endforeach ?>
    </tbody>
</table>

<label>
    <?= _("Hinzukommende Termine in meinem Kalender berücksichtigen") ?>
    <select name="answers[<?= $vote->getId() ?>][answerdata][mode]" style="max-width: 100%;">
        <option value="dynamic"><?= _("Ausgewählte Termine automatisch abwählen, wenn sich mein Kalender füllt.") ?></option>
        <option value="static"><?= _("Hier ausgewählten Terminen auf jeden Fall zusagen.") ?></option>
    </select>
</label>
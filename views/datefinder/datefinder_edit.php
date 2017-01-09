<? $etask = $vote->etask; ?>

<label>
    <?= _("Thema") ?>
    <textarea name="questions[<?= $vote->getId() ?>][questiondata][question]" placeholder="<?= _("Wann wollen wir ...") ?>"><?= isset($etask->description) ? htmlReady($etask->description) : "" ?></textarea>
</label>

<label>
    <?= _("Mindestdauer des Termins in Stunden") ?>
    <input type="number" value="<?= $vote->isNew() ? 2 : htmlReady($etask->task['duration']) ?>" name="questions[<?= $vote->getId() ?>][questiondata][duration]">
</label>

<? $emptyAnswerTemplate = $this->render_partial('datefinder/_option', [ 'vote' => $vote, 'option' => '' ]) ?>
<ol class="clean options" data-optiontemplate="<?= htmlReady($emptyAnswerTemplate) ?>">
    <? if (isset($etask->task['dates']) && $etask->task['dates']) : ?>
        <? foreach ($etask->task['dates'] as $date) : ?>
        <?= $this->render_partial("datefinder/_option.php", array('vote' => $vote, 'option' => $date)) ?>
        <? endforeach ?>
    <? endif ?>
    <?= $emptyAnswerTemplate ?>
</ol>

<label>
    <input type="checkbox" name="questions[<?= $vote->getId() ?>][questiondata][automatic]" value="1"<?= $vote->isNew() || $etask->task['automatic'] ? " checked" : ""?>>
    <?= _("Nach Ende der Terminfindung automatisch den besten Termin auswählen und eintragen") ?>
</label>

<div style="display: none" class="delete_question"><?= _("Diesen Zeitpunkt wirklich löschen?") ?></div>

<script>
    jQuery(function () {
        $(document).on("focus", ".options .date:not(.hasDatepicker)", function () {
            $(this).datepicker();
        });
        $(document).on("focus", ".options .time:not(.hasDatepicker)", function () {
            $(this).timepicker();
        });
    });
</script>

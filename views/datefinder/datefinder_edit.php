<label>
    <?= _("Thema") ?>
    <textarea name="questions[<?= $vote->getId() ?>][questiondata][question]" placeholder="<?= _("Wann wollen wir ...") ?>"><?= isset($vote['questiondata']['question']) ? htmlReady($vote['questiondata']['question']) : "" ?></textarea>
</label>

<label>
    <?= _("Mindestdauer des Termins in Stunden") ?>
    <input type="number" value="<?= $vote->isNew() ? 2 : htmlReady($vote['questiondata']['duration']) ?>" name="questions[<?= $vote->getId() ?>][questiondata][duration]">
</label>

<ol class="clean options" data-optiontemplate="<?= htmlReady($this->render_partial("datefinder/_option.php", array('vote' => $vote, 'option' => ""))) ?>">
    <? if (isset($vote['questiondata']['dates']) && $vote['questiondata']['dates']) : ?>
        <? foreach ($vote['questiondata']['dates'] as $date) : ?>
        <?= $this->render_partial("datefinder/_option.php", array('vote' => $vote, 'option' => $date)) ?>
        <? endforeach ?>
    <? endif ?>
    <?= $this->render_partial("datefinder/_option.php", array('vote' => $vote, 'option' => "")) ?>
</ol>

<label>
    <input type="checkbox" name="questions[<?= $vote->getId() ?>][questiondata][automatic]" value="1"<?= $vote->isNew() || $vote['questiondata'] ? " checked" : ""?>>
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
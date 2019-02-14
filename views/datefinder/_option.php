<li>
    <input type="text"
           name="questions[<?= $vote->getId() ?>][questiondata][day][]"
           value="<?= $option ? date("d.m.Y", $option) : "" ?>"
           placeholder="<?= _("Tag ...") ?>"
           aria-label="<?= _("Geben Sie einen möglichen Termin an.") ?>"
           class="date"
           style="width: calc(50% - 30px); display: inline;">
    <input type="text"
           name="questions[<?= $vote->getId() ?>][questiondata][time][]"
           value="<?= $option ? date("H:i", $option) : "" ?>"
           placeholder="<?= _("Uhrzeit ...") ?>"
           aria-label="<?= _("Geben Sie einen möglichen Termin an.") ?>"
           class="time"
           style="width: calc(50% - 30px); display: inline;">
    <?= Icon::create('trash', 'clickable', [ 'title' => _("Termin löschen") ])->asImg(20, [ 'class' => 'delete' ]) ?>
</li>

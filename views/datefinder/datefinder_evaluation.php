<? $etask = $vote->etask; ?>
<h3>
    <?= formatReady($etask->description) ?>
</h3>

<?
$taskDates = $etask->task['dates'] ?: [];
$results = array();
$results_users = array();
foreach ($taskDates as $date) {
    $results[$date] = 0;
    $results_users[$date] = array();
}
foreach ($vote->answers as $answer) {
    $answerdata = $answer['answerdata']->getArrayCopy();
    $dates = $answerdata['mode'] === "dynamic"
        ? $answerdata['filtereddates']
        : $answerdata['dates'];
    foreach ((array) $dates as $date) {
        $results[$date]++;
        $results_users[$date][] = $answer['user_id'];
    }
}
?>

<? if ($etask->task['status'] === "needsmanualevaluation") :
    $best = array();
    $new_best = array();
    foreach ($taskDates as $date) {
        if (!count($best) || ($results[$date] > $results[$best[0]])) {
            $best = array($date);
        } elseif ($results[$date] == $results[$best[0]]) {
            $best[] = $date;
        }
    }
    ?>
    <div style="text-align: center;">
        <? foreach ($best as $bestdate) : ?>
            <? if ($vote->questionnaire->isEditable()) : ?>
                <a href="<?= URLHelper::getLink("plugins.php/datefinderplugin/admin/choose_date/".$vote->getId()."/".$bestdate) ?>"
                   title="<?= _("Wählen Sie diesen Termin aus.") ?>"
                   onClick="return window.confirm('<?= _("Soll dieser Termin ausgewählt werden?") ?>');"
                   <?= Request::isAjax() ? "data-dialog" : "" ?>>
            <? endif ?>
            <div class="select_date" style="margin: 5px; padding: 5px; border: thin solid #b8c2d5; display: inline-block;">
                <div style="font-size: 2em;"><?= date("H:i", $bestdate) ?></div>
                <div><?= date("d.m.Y", $bestdate) ?></div>
            </div>
            <? if ($vote->questionnaire->isEditable()) : ?>
                </a>
            <? endif ?>
        <? endforeach ?>
    </div>
<? endif ?>

<? if ($etask->task['status'] === "founddate") : ?>
    <div style="text-align: center;">
        <div class="selected_date" style="margin: 5px; padding: 5px; border: thin solid #b8c2d5; display: inline-block;">
            <?= Icon::create("accept", "status-green")->asImg(28) ?>
            <div style="font-size: 2em;"><?= date("H:i", $etask->task['founddate']) ?></div>
            <div><?= date("d.m.Y", $etask->task['founddate']) ?></div>
        </div>
    </div>
<? endif ?>

<table class="default nohover">
    <tbody>
    <? $countAnswers = $vote->questionnaire->countAnswers() ?>
    <? foreach ($etask->task['dates'] as $key => $date) : ?>
        <tr>
            <? $percentage = $countAnswers ? round((int) $results[$date] / $countAnswers * 100) : 0 ?>
            <td style="text-align: right; background-size: <?= $percentage ?>% 100%; background-position: right center; background-image: url('<?= Assets::image_path("vote_lightgrey.png") ?>'); background-repeat: no-repeat;" width="50%">
                <strong><?= date("d.m.Y H:i", $date) ?></strong>
            </td>
            <td style="white-space: nowrap;">
                (<?= $percentage ?>%
                | <?= (int) $results[$date] ?>/<?= $countAnswers ?>)
            </td>
            <td width="50%">
                <? if (!$vote->questionnaire['anonymous'] && $results[$date]) : ?>
                    <? foreach ($results_users[$date] as $index => $user_id) : ?>
                        <? if ($user_id && $user_id !== "nobody") : ?>
                            <a href="<?= URLHelper::getLink("dispatch.php/profile", array('username' => get_username($user_id))) ?>">
                                <?= Avatar::getAvatar($user_id, get_username($user_id))->getImageTag(Avatar::SMALL, array('title' => htmlReady(get_fullname($user_id)))) ?>
                                <? if (count($results_users[$date]) < 4) : ?>
                                    <?= htmlReady(get_fullname($user_id)) ?>
                                <? endif ?>
                            </a>
                        <? endif ?>
                    <? endforeach ?>
                <? endif ?>
            </td>
        </tr>
    <? endforeach ?>
    </tbody>
</table>

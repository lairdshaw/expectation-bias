<?php

/**
 * A PHP web script which I used to generate the table in this post to the Skeptiko forum:
 * http://www.skeptiko-forum.com/threads/presentiment-paper-discussion.2287/page-15#post-72972
 *
 * I don't have the patience to contextualise it other than to say that it analyses
 * the hypothetical "expectation bias" which parapsychological researchers have identified as
 * potentially affecting presentiment experiments. The three papers where this bias is discussed
 * which are raised in that thread are:
 *
 * Dalkvist et al, 2002, "A computational expectation bias as revealed by simulations of presentiment experiments"
 * Wackermann, 2002, "On cumulative effects and averaging artefacts in randomised S-R experimental designs", and,
 * Dalkvist et al, 2014, "How to remove the influence of expectation bias in presentiment and similar experiments: a recommended strategy".
 *
 * To run the script, simply place it on a PHP-capable web server, and browse to it. If you would like to have
 * fixed header rows and columns to make viewing large tables easier, then also copy the following two files into the
 * same directory as the script:
 *
 * <http://creativeandcritical.net/js/jquery.min.js> and
 * <http://creativeandcritical.net/js/jquery.fixedtableheaderandcols.js>
 *
 * I release the code in this file into the public domain with the caveats that I:
 *
 * 1. Assert original authorship of this code, and the moral and legal right to be identified as its original author.
 * 2. Deny anybody else the right to falsely claim authorship over it other than of any changes that they make to it.
 *
 * Author : Laird Shaw.
 * Date   : 2015-07-28.
 * Version: 7.
 *
 * Changelog:
 *
 * Version 7, 2015-07-28: Added support for pooled averaging analysis: multiple sequences per experiment.
 *                        Made some minor textual corrections to headings.
 *                        Minor bugfix: test for division by 0 with != rather than !=== because the zero value
                           might be a float rather than an integer.
 * Version 6, 2015-07-27: Bugfix: final table shouldn't have had fixed columns.
 *                        Bugfix: one of the difference summary cells was wrong due to an extraneous
 *                                negation symbol.
 *                        Bugfix: don't weight per-number-of-calm-trial sums by #sequences, only by probability.
 *                        Minor bugfix: test for division by 0 with != rather than !== because the zero value
 *                         might be a float rather than an integer.
 *                        Added missing rounding for the difference rows.
 * Version 5, 2015-07-26: Added support for varying the probability of emotional (and thus calm) trials.
 *                        Added several columns for weighting by probability (and #sequences).
 *                        Added some summary rows for differences.
 *                        Added support for fixed table header rows and table columns, contingent on the
 *                         presence of a couple of Javascript files.
 * Version 4, 2015-07-23: Minor bugfix: '0%' => 'n/a'.
 * Version 3, 2015-07-22: Added several new columns to the tables, including weighting columns.
 *                        Added different types of calculations of expectation bias in a table at the end, including
 *                         a calculation by Wackermann's formula, the result of which is scaled for arousal increments
 *                         other than one.
 *                        Added an indication that probabilities of calm and emotional trials are hard-coded to 0.5.
 *                        (The next step in the evolution of this script is to allow these values to be user-configurable).
 * Version 2, 2015-07-19: Added trial sampling. Allowed arousal reset/increment to be floats.
 * Version 1, 2015-07-19: Initial release.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<title>Examining theoretical expectation bias phenomena in presentiment experiments especially with respect to sequences of a given number of calm/emotional trials</title>
<script type="text/javascript" src="jquery.min.js"></script>
<script type="text/javascript" src="jquery.fixedtableheaderandcols.js"></script>
<script type="text/javascript">
//<![CDATA[
if (typeof $ == 'function') {
	$(document).ready(function() {
		$('.tbl_fixed').fixedtableheader({
			headerrowsize: 3,
			fixedcolssize: 2
		});
	});
}
//]]>
</script>
<style type="text/css">
.tbl_data {
	border-collapse: collapse;
}
.tbl_data td, .tbl_data th {
	text-align: center;
	border: 1px solid black;
	padding: 3px;
	background-color: white;
}
</style>
</head>

<body>

<h2>Examining theoretical expectation bias phenomena in presentiment experiments especially with respect to sequences of a given number of calm/emotional trials</h2>
	
<?php
/* Set defaults */
$num_seq_per_exp    = 1;
$num_trials_per_seq = 7;
$reset_arousal      = 0;
$arousal_inc        = 1;
$prob_e             = 0.5;
$rounding_precision = 6;
$sample_start       = 1;
$sample_inc         = 1;
$show_details       = false;

if (isset($_GET['num_trials_per_seq'])) {
	$per_seq_stats = $per_num_calm_stats = $per_num_calm_summary_stats = array();
	$per_seq_summary_stats = array(
		'sum_prob'        => 0,
		'count_prob'      => 0,
		'sum_C_avgs'      => 0,
		'count_C_avgs'    => 0,
		'sum_E_avgs'      => 0,
		'count_E_avgs'    => 0,
		'sum_C_sums'      => 0,
		'count_C_sums'    => 0,
		'sum_E_sums'      => 0,
		'count_E_sums'    => 0,
		'sum_diff_avgs'   => 0,
		'count_diff_avgs' => 0,
		'sum_diff_sums'   => 0,
		'count_diff_sums' => 0,
		'sum_C_avgs_for_defined_diffs'   => 0,
		'count_C_avgs_for_defined_diffs' => 0,
		'sum_C_avgs_prob'      => 0,
		'count_C_avgs_prob'    => 0,
		'sum_E_avgs_prob'      => 0,
		'count_E_avgs_prob'    => 0,
		'sum_C_sums_prob'      => 0,
		'count_C_sums_prob'    => 0,
		'sum_E_sums_prob'      => 0,
		'count_E_sums_prob'    => 0,
		'sum_diff_avgs_prob'   => 0,
		'count_diff_avgs_prob' => 0,
		'sum_diff_sums_prob'   => 0,
		'count_diff_sums_prob' => 0,
		'sum_C_avgs_for_defined_diffs_prob'   => 0,
		'count_C_avgs_for_defined_diffs_prob' => 0,
	);
	$num_seq_per_exp = (int)$_GET['num_seq_per_exp'];
	if ($num_seq_per_exp < 1) $num_seq_per_exp = 1;
	$lbl_seq_or_exp = ($num_seq_per_exp == 1 ? 'sequence' : 'experiment');
	$num_trials_per_seq = (int)$_GET['num_trials_per_seq'];
	if ($num_trials_per_seq < 1) $num_trials_per_seq = 1;
	$reset_arousal = (float)$_GET['reset_arousal'];
	$arousal_inc = (float)$_GET['arousal_inc'];
	$prob_e = (float)$_GET['prob_e'];
	if ($prob_e < 0) $prob_e = 0;
	else if ($prob_e > 1) $prob_e = 1;
	$rounding_precision = (int)$_GET['rounding_precision'];
	$sample_start = (int)$_GET['sample_start'];
	if ($sample_start < 1) $sample_start = 1;
	$sample_inc = (int)$_GET['sample_inc'];
	if ($sample_inc < 1) $sample_inc = 1;
	$show_details = isset($_GET['show_details']);

	calc_stats_r('', 0, $num_trials_per_seq * $num_seq_per_exp, $per_seq_stats, $per_num_calm_stats, $per_seq_summary_stats);
	calc_final_per_num_calm_stats($per_num_calm_stats, $per_num_calm_summary_stats);

	$per_seq_avgs = get_avgs($per_seq_summary_stats, array('prob', 'C_avgs', 'E_avgs', 'diff_avgs', 'C_sums', 'E_sums', 'diff_sums', 'C_avgs_for_defined_diffs', 'C_avgs_prob', 'E_avgs_prob', 'diff_avgs_prob', 'C_sums_prob', 'E_sums_prob', 'diff_sums_prob', 'C_avgs_for_defined_diffs_prob'));

	$per_seq_diff_avg_avgs = diff_or_na($per_seq_avgs['avg_E_avgs'], $per_seq_avgs['avg_C_avgs']);
	$per_seq_diff_avg_sums = diff_or_na($per_seq_avgs['avg_E_sums'], $per_seq_avgs['avg_C_sums']);
	$per_seq_diff_avg_avgs_prob = diff_or_na($per_seq_avgs['avg_E_avgs_prob'], $per_seq_avgs['avg_C_avgs_prob']);
	$per_seq_diff_avg_sums_prob = diff_or_na($per_seq_avgs['avg_E_sums_prob'], $per_seq_avgs['avg_C_sums_prob']);
	$per_seq_diff_sum_avgs = diff_or_na($per_seq_summary_stats['sum_E_avgs'], $per_seq_summary_stats['sum_C_avgs']);
	$per_seq_diff_sum_sums = diff_or_na($per_seq_summary_stats['sum_E_sums'], $per_seq_summary_stats['sum_C_sums']);
	$per_seq_diff_sum_avgs_prob = diff_or_na($per_seq_summary_stats['sum_E_avgs_prob'], $per_seq_summary_stats['sum_C_avgs_prob']);
	$per_seq_diff_sum_sums_prob = diff_or_na($per_seq_summary_stats['sum_E_sums_prob'], $per_seq_summary_stats['sum_C_sums_prob']);

	$num_calm_avgs = get_avgs($per_num_calm_summary_stats, array('C_avg_avgs', 'E_avg_avgs', 'C_weighted_avg_avgs', 'E_weighted_avg_avgs', 'C_sum_avgs', 'E_sum_avgs', 'C_weighted_sum_avgs', 'E_weighted_sum_avgs', 'C_avg_sums', 'E_avg_sums', 'C_sum_sums', 'E_sum_sums', 'C_weighted_avg_sums', 'E_weighted_avg_sums', 'C_weighted_sum_sums', 'E_weighted_sum_sums', '#seq', 'prob', '#seq_prob'));

	$per_num_calm_diff_avg_avg_avgs = diff_or_na($num_calm_avgs['avg_E_avg_avgs'], $num_calm_avgs['avg_C_avg_avgs']);
	$per_num_calm_diff_avg_weighted_avg_avgs = diff_or_na($num_calm_avgs['avg_E_weighted_avg_avgs'], $num_calm_avgs['avg_C_weighted_avg_avgs']);
	$per_num_calm_diff_avg_sum_avgs = diff_or_na($num_calm_avgs['avg_E_sum_avgs'], $num_calm_avgs['avg_C_sum_avgs']);
	$per_num_calm_diff_avg_weighted_sum_avgs = diff_or_na($num_calm_avgs['avg_E_weighted_sum_avgs'], $num_calm_avgs['avg_C_weighted_sum_avgs']);
	$per_num_calm_diff_avg_avg_sums = diff_or_na($num_calm_avgs['avg_E_avg_sums'], $num_calm_avgs['avg_C_avg_sums']);
	$per_num_calm_diff_avg_weighted_avg_sums = diff_or_na($num_calm_avgs['avg_E_weighted_avg_sums'], $num_calm_avgs['avg_C_weighted_avg_sums']);
	$per_num_calm_diff_avg_sum_sums = diff_or_na($num_calm_avgs['avg_E_sum_sums'], $num_calm_avgs['avg_C_sum_sums']);
	$per_num_calm_diff_avg_weighted_sum_sums = diff_or_na($num_calm_avgs['avg_E_weighted_sum_sums'], $num_calm_avgs['avg_C_weighted_sum_sums']);
?>

<p>Method of analysis: <?php echo ($num_seq_per_exp == 1 ? 'per-sequence' : 'pooled'); ?> averaging.</p>

<?php
	if ($num_seq_per_exp != 1) {
?>
<p>Number of sequences  per experiment: <?php echo $num_seq_per_exp; ?>.</p>

<?php
	}
?>
<p>Number of trials per sequence: <?php echo $num_trials_per_seq; ?>.</p>

<p>Reset arousal level: <?php echo $reset_arousal; ?>.</p>

<p>Arousal increment: <?php echo $arousal_inc; ?>.</p>

<p>Number of decimal places to round to: <?php echo $rounding_precision; ?>.</p>

<p>Sampling within each sequence every <?php echo $sample_inc; ?> trials beginning from (leftmost) trial <?php echo $sample_start; ?>.</p>

<p>E = emotional trial (after which arousal resets).</p>

<p>C = calm trial (after which arousal increments).</p>

<p>Probability(E) = <?php echo $prob_e; ?>.</p>

<p>Probability(C) = (1 - Probability(E)) = <?php echo (1 - $prob_e); ?>.</p>

<p>Arousal starts at reset level. Arousal level for a particular trial is the arousal level <i>preceding</i> that trial. Averages are with respect to these preceding arousal levels across all <i>sampled</i> calm/emotional trials in the sequence.</p> 

<?php
	if ($show_details) {
?>
<h2>Per-<?php echo $lbl_seq_or_exp; ?> arousals</h2>

<table class="tbl_data tbl_fixed" id="id_per_seq_stats">
<tr><th><?php echo ucfirst($lbl_seq_or_exp); ?></th><th>Probability</th><th colspan="4">Average arousal</th><th colspan="2">Difference of averages</th><th colspan="4">Sum of arousals</th><th colspan="2">Difference of sums</th></tr>
<tr><th></th><th></th><th colspan="2">Raw</th><th colspan="2">Weighted by probability</th><th>Raw</th><th>Weighted by probability</th><th colspan="2">Raw</th><th colspan="2">Weighted by probability</th><th>Raw</th><th>Weighted by probability</th></tr>
<tr><th></th><th></th><th>C</th><th>E</th><th>C</th><th>E</th><th></th><th></th><th>C</th><th>E</th><th>C</th><th>E</th><th></th><th></th></tr>
<?php
		foreach ($per_seq_stats as $seq => $seq_stats) {
			echo "<tr><td>".format_seq($seq)."</td><td>".rounded_or_na($seq_stats['prob'])."</td><td>".rounded_or_na($seq_stats['avgs']['C'])."</td><td>".rounded_or_na($seq_stats['avgs']['E'])."</td><td>".rounded_or_na($seq_stats['avgs']['C'] === 'n/a' ? 'n/a' : $seq_stats['avgs']['C']*$seq_stats['prob'])."</td><td>".rounded_or_na($seq_stats['avgs']['E'] === 'n/a' ? 'n/a' : $seq_stats['avgs']['E']*$seq_stats['prob'])."</td><td>".rounded_or_na($seq_stats['diff_avgs'])."</td><td>".rounded_or_na($seq_stats['diff_avgs'] === 'n/a' ? 'n/a' : $seq_stats['diff_avgs']*$seq_stats['prob'])."</td><td>".rounded_or_na($seq_stats['sums']['C'])."</td><td>".rounded_or_na($seq_stats['sums']['E'])."</td><td>".rounded_or_na($seq_stats['sums']['C'] === 'n/a' ? 'n/a' : $seq_stats['sums']['C']*$seq_stats['prob'])."</td><td>".rounded_or_na($seq_stats['sums']['E'] === 'n/a' ? 'n/a' : $seq_stats['sums']['E']*$seq_stats['prob'])."</td><td>".rounded_or_na($seq_stats['diff_sums'])."</td><td>".rounded_or_na($seq_stats['diff_sums'] === 'n/a' ? 'n/a' : $seq_stats['diff_sums']*$seq_stats['prob'])."</td></tr>\n";
		}
		echo '<tr><th>Totals:</th><th>'.rounded_or_na($per_seq_summary_stats['sum_prob']).'</th><th>'.rounded_or_na($per_seq_summary_stats['sum_C_avgs']).'<th>'.rounded_or_na($per_seq_summary_stats['sum_E_avgs']).'</th><th>'.rounded_or_na($per_seq_summary_stats['sum_C_avgs_prob']).'<th>'.rounded_or_na($per_seq_summary_stats['sum_E_avgs_prob']).'</th><th>'.rounded_or_na($per_seq_summary_stats['sum_diff_avgs']).'</th><th>'.rounded_or_na($per_seq_summary_stats['sum_diff_avgs_prob']).'</th><th>'.rounded_or_na($per_seq_summary_stats['sum_C_sums']).'</th><th>'.rounded_or_na($per_seq_summary_stats['sum_E_sums']).'</th><th>'.rounded_or_na($per_seq_summary_stats['sum_C_sums_prob']).'</th><th>'.rounded_or_na($per_seq_summary_stats['sum_E_sums_prob']).'</th><th>'.rounded_or_na($per_seq_summary_stats['sum_diff_sums']).'</th><th>'.rounded_or_na($per_seq_summary_stats['sum_diff_sums_prob']).'</th></tr>'."\n";
		echo '<tr><th>Averages:</th><th>'.rounded_or_na($per_seq_avgs['avg_prob']).'</th><th>'.rounded_or_na($per_seq_avgs['avg_C_avgs']).'</th><th>'.rounded_or_na($per_seq_avgs['avg_E_avgs']).'</th><th>'.rounded_or_na($per_seq_avgs['avg_C_avgs_prob']).'</th><th>'.rounded_or_na($per_seq_avgs['avg_E_avgs_prob']).'</th><th>'.rounded_or_na($per_seq_avgs['avg_diff_avgs']).'</th><th>'.rounded_or_na($per_seq_avgs['avg_diff_avgs_prob']).'</th><th>'.rounded_or_na($per_seq_avgs['avg_C_sums']).'</th><th>'.rounded_or_na($per_seq_avgs['avg_E_sums']).'</th><th>'.rounded_or_na($per_seq_avgs['avg_C_sums_prob']).'</th><th>'.rounded_or_na($per_seq_avgs['avg_E_sums_prob']).'</th><th>'.rounded_or_na($per_seq_avgs['avg_diff_sums']).'</th><th>'.rounded_or_na($per_seq_avgs['avg_diff_sums_prob']).'</th></tr>'."\n";

		echo '<tr><th>Differences (Totals):</th><th></th><th colspan="2">'.rounded_or_na($per_seq_diff_sum_avgs).'</th><th colspan="2">'.rounded_or_na($per_seq_diff_sum_avgs_prob).'</th><th></th><th></th><th colspan="2">'.rounded_or_na($per_seq_diff_sum_sums).'</th><th colspan="2">'.rounded_or_na($per_seq_diff_sum_sums_prob).'</th><th></th><th></th></tr>'."\n";
		echo '<tr><th>Differences (Averages):</th><th></th><th colspan="2">'.rounded_or_na($per_seq_diff_avg_avgs).'</th><th colspan="2">'.rounded_or_na($per_seq_diff_avg_avgs_prob).'</th><th></th><th></th><th colspan="2">'.rounded_or_na($per_seq_diff_avg_sums).'</th><th colspan="2">'.rounded_or_na($per_seq_diff_avg_sums_prob).'</th><th></th><th></th></tr>'."\n";
?>
</table>

<h2>Per-<?php echo $lbl_seq_or_exp; ?> average arousals for given numbers of calm/emotional trials</h2>

<p>As extracted from the above "Per-<?php echo $lbl_seq_or_exp; ?> arousals" table.</p>

<table class="tbl_data tbl_fixed">
<tr><th>#C</th><th>#E</th><th>Probability</th><th colspan="4">Average Arousals</th></tr>
<tr><th></th><th></th><th></th><th colspan="2">Raw</th><th colspan="2">Weighted by probability</th></tr>
<tr><th></th><th></th><th></th><th>C</th><th>E</th><th>C</th><th>E</th></tr>
<?php
		foreach ($per_num_calm_stats as $num_calm => $stats) {
			echo "<tr><td>$num_calm</td><td>".($num_trials_per_seq * $num_seq_per_exp - $num_calm)."</td><td>{$stats['prob']}</td><td>".get_rounded_string_list($stats['C_avgs'])."</td><td>".get_rounded_string_list($stats['E_avgs'])."</td><td>".get_rounded_string_list($stats['C_avgs'], $stats['prob'])."</td><td>".get_rounded_string_list($stats['E_avgs'], $stats['prob']).'</td></tr>'."\n";
		}
?>
</table>

<h2>Per-<?php echo $lbl_seq_or_exp; ?> sums of arousals for given numbers of calm/emotional trials</h2>

<p>As extracted from the above "Per-<?php echo $lbl_seq_or_exp; ?> arousals" table.</p>

<table class="tbl_data tbl_fixed">
<tr><th>#C</th><th>#E</th><th>Probability</th><th colspan="4">Sums of Arousals</th></tr>
<tr><th></th><th></th><th></th><th colspan="2">Raw</th><th colspan="2">Weighted by probability</th></tr>
<tr><th></th><th></th><th></th><th>C</th><th>E</th><th>C</th><th>E</th></tr>
<?php
		foreach ($per_num_calm_stats as $num_calm => $stats) {
			echo "<tr><td>$num_calm</td><td>".($num_trials_per_seq * $num_seq_per_exp - $num_calm)."</td><td>{$stats['prob']}</td><td>".get_rounded_string_list($stats['C_sums'])."</td><td>".get_rounded_string_list($stats['E_sums'])."</td><td>".get_rounded_string_list($stats['C_sums'], $stats['prob'])."</td><td>".get_rounded_string_list($stats['E_sums'], $stats['prob']).'</td></tr>'."\n";
		}
?>
</table>

<?php
	}
?>

<h2>Sums and averages of per-<?php echo $lbl_seq_or_exp; ?> sums and averages of arousals for given numbers of calm/emotional trials</h2>

<?php
	if ($show_details) {
		echo '<p>As derived from the above two tables.</p>'."\n";
	}
?>

<table class="tbl_data tbl_fixed" id="id_per_num_calm_stats">
<tr><th>#C</th><th>#E</th><th>#<?php echo $lbl_seq_or_exp.'s'; ?></th><th>Probability</th><th>Probability weighted by #<?php echo $lbl_seq_or_exp.'s'; ?></th><th colspan="4">Average of per-<?php echo $lbl_seq_or_exp; ?> average arousals</th><th colspan="4">Sum of per-<?php echo $lbl_seq_or_exp; ?> average arousals</th><th colspan="4">Average of per-<?php echo $lbl_seq_or_exp; ?> sums of arousal</th><th colspan="4">Sum of per-<?php echo $lbl_seq_or_exp; ?> sums of arousal</th></tr>
<tr><th></th><th></th><th></th><th></th><th></th><th colspan="2">Raw</th><th colspan="2">Weighted by #<?php echo $lbl_seq_or_exp.'s'; ?> and probability</th><th colspan="2">Raw</th><th colspan="2">Weighted by probability</th><th colspan="2">Raw</th><th colspan="2">Weighted by #<?php echo $lbl_seq_or_exp.'s'; ?> and probability</th><th colspan="2">Raw</th><th colspan="2">Weighted by probability</th></tr>
<tr><th></th><th></th><th></th><th></th><th></th><th>C</th><th>E</th><th>C</th><th>E</th><th>C</th><th>E</th><th>C</th><th>E</th><th>C</th><th>E</th><th>C</th><th>E</th><th>C</th><th>E</th><th>C</th><th>E</th></tr>
<?php
	foreach ($per_num_calm_stats as $num_calm => $stats) {
		echo "<tr><td>$num_calm</td><td>".($num_trials_per_seq * $num_seq_per_exp - $num_calm)."</td><td>{$stats['#seq']}</td><td>".rounded_or_na($stats['prob'])."</td><td>".rounded_or_na($stats['prob']*$stats['#seq'])."</td><td>".rounded_or_na($stats['C_avg_avgs'])."</td><td>".rounded_or_na($stats['E_avg_avgs'])."</td><td>".rounded_or_na($stats['C_weighted_avg_avgs'])."</td><td>".rounded_or_na($stats['E_weighted_avg_avgs'])."</td><td>".rounded_or_na($stats['C_sum_avgs'])."</td><td>".rounded_or_na($stats['E_sum_avgs'])."</td><td>".rounded_or_na($stats['C_sum_avgs']*$stats['prob']*$stats['#seq'])."</td><td>".rounded_or_na($stats['E_sum_avgs']*$stats['prob']*$stats['#seq'])."</td><td>".rounded_or_na($stats['C_avg_sums'])."</td><td>".rounded_or_na($stats['E_avg_sums'])."</td><td>".rounded_or_na($stats['C_avg_sums']*$stats['prob'])."</td><td>".rounded_or_na($stats['E_avg_sums']*$stats['prob'])."</td><td>".rounded_or_na($stats['C_sum_sums'])."</td><td>".rounded_or_na($stats['E_sum_sums'])."</td><td>".rounded_or_na($stats['C_sum_sums']*$stats['prob'])."</td><td>".rounded_or_na($stats['E_sum_sums']*$stats['prob'])."</td></tr>\n";
	}
?>
<tr><th>Totals:</th><th></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_#seq']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_prob']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_#seq_prob']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_C_avg_avgs']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_avg_avgs']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_C_weighted_avg_avgs']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_weighted_avg_avgs']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_C_sum_avgs']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_sum_avgs']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_C_weighted_sum_avgs']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_weighted_sum_avgs']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_C_avg_sums']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_avg_sums']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_C_weighted_avg_sums']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_weighted_avg_sums']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_C_sum_sums']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_sum_sums']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_C_weighted_sum_sums']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_weighted_sum_sums']); ?></th></tr>
<tr><th>Averages:</th><th></th><th><?php echo rounded_or_na($num_calm_avgs['avg_#seq']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_prob']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_#seq_prob']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_C_avg_avgs']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_E_avg_avgs']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_C_weighted_avg_avgs']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_E_weighted_avg_avgs']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_C_sum_avgs']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_E_sum_avgs']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_C_weighted_sum_avgs']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_E_weighted_sum_avgs']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_C_avg_sums']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_E_avg_sums']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_C_weighted_avg_sums']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_E_weighted_avg_sums']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_C_sum_sums']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_E_sum_sums']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_C_weighted_sum_sums']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_E_weighted_sum_sums']); ?></th></tr>
<tr><th>Differences (Totals):</th><th></th><th></th><th></th><th></th><th colspan="2"><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_avg_avgs'] - $per_num_calm_summary_stats['sum_C_avg_avgs']); ?></th><th colspan="2"><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_weighted_avg_avgs'] - $per_num_calm_summary_stats['sum_C_weighted_avg_avgs']); ?></th><th colspan="2"><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_sum_avgs'] - $per_num_calm_summary_stats['sum_C_sum_avgs']); ?></th><th colspan="2"><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_weighted_sum_avgs'] - $per_num_calm_summary_stats['sum_C_weighted_sum_avgs']); ?></th><th colspan="2"><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_avg_sums'] - $per_num_calm_summary_stats['sum_C_avg_sums']); ?></th><th colspan="2"><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_weighted_avg_sums'] - $per_num_calm_summary_stats['sum_C_weighted_avg_sums']); ?></th><th colspan="2"><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_sum_sums'] - $per_num_calm_summary_stats['sum_C_sum_sums']); ?></th><th colspan="2"><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_weighted_sum_sums'] - $per_num_calm_summary_stats['sum_C_weighted_sum_sums']); ?></th></tr>
<tr><th>Differences (Averages):</th><th></th><th></th><th></th><th></th><th colspan="2"><?php echo rounded_or_na($per_num_calm_diff_avg_avg_avgs); ?></th><th colspan="2"><?php echo rounded_or_na($per_num_calm_diff_avg_weighted_avg_avgs); ?></th><th colspan="2"><?php echo rounded_or_na($per_num_calm_diff_avg_sum_avgs); ?></th><th colspan="2"><?php echo rounded_or_na($per_num_calm_diff_avg_weighted_sum_avgs); ?></th><th colspan="2"><?php echo rounded_or_na($per_num_calm_diff_avg_avg_sums); ?></th><th colspan="2"><?php echo rounded_or_na($per_num_calm_diff_avg_weighted_avg_sums); ?></th><th colspan="2"><?php echo rounded_or_na($per_num_calm_diff_avg_sum_sums); ?></th><th colspan="2"><?php echo rounded_or_na($per_num_calm_diff_avg_weighted_sum_sums); ?></th></tr>
</table>

<h2>Expectation bias</h2>

<?php
	$C_weighted_avgs_unweighted = $per_num_calm_summary_stats['sum_C_weighted_avg_avgs'];
	$diff_weighted_avgs_unweighted = ($per_num_calm_summary_stats['sum_E_weighted_avg_avgs'] - $per_num_calm_summary_stats['sum_C_weighted_avg_avgs']); 
	$sum_C_avgs_prob_setting_na_to_zero = $per_seq_summary_stats['count_C_avgs_for_defined_diffs'] !== 0 ? $per_seq_summary_stats['sum_C_avgs_for_defined_diffs_prob'] * $per_seq_summary_stats['count_C_avgs_for_defined_diffs'] / pow(2, $num_trials_per_seq * $num_seq_per_exp) : 0;

	$do_wackermann = ($num_seq_per_exp == 1);
	if ($do_wackermann) {
		$scale_wackermann = ($arousal_inc != 1);
		$are_sampling = !($sample_start == 1 && $sample_inc == 1);
		$wackermann_diff = calc_wackermann_diff($num_trials_per_seq, $prob_e);
		if ($scale_wackermann) $wackermann_diff *= $arousal_inc;
		$wackerman_label_appendage = '';
		if ($scale_wackermann) {
			$wackerman_label_appendage .= ' (scaled';
		}
		if ($are_sampling) {
			$wackerman_label_appendage .= $wackerman_label_appendage == '' ? ' (' : ', ';
			$wackerman_label_appendage .= 'without sampling';
		}
		if ($wackerman_label_appendage) {
			$wackerman_label_appendage .= ')';
		}
	}
?>

<table class="tbl_data">
<tr><th>Method</th><th>Average bias in per-<?php echo $lbl_seq_or_exp; ?> average arousals for emotional versus calm trials</th><th>Average of per-<?php echo $lbl_seq_or_exp; ?> average arousals for calm trials</th><th>Expectation bias as a percentage of average calm arousal</th></tr>
<tr><td>Average of per-<?php echo $lbl_seq_or_exp; ?> differences (excluding n/a's)</td><td><?php echo rounded_or_na($per_seq_summary_stats['sum_diff_avgs_prob']); ?></td><td><?php echo rounded_or_na($per_seq_summary_stats['sum_C_avgs_for_defined_diffs_prob']); ?></td><td><?php echo ($per_seq_summary_stats['sum_C_avgs_for_defined_diffs_prob'] !=/*Not !== because might be a float*/ 0 && $per_seq_summary_stats['sum_C_avgs_for_defined_diffs_prob'] !== 'n/a' && $per_seq_summary_stats['sum_diff_avgs_prob'] !== 'n/a') ? rounded_or_na($per_seq_summary_stats['sum_diff_avgs_prob'] / $per_seq_summary_stats['sum_C_avgs_for_defined_diffs_prob'] * 100).'%' : 'n/a'; ?></td></tr>
<tr><td>Average of per-<?php echo $lbl_seq_or_exp; ?> differences (setting n/a's to zero)</td><td><?php echo rounded_or_na($per_seq_summary_stats['sum_diff_avgs_prob'] * $per_seq_summary_stats['count_diff_avgs_prob'] / pow(2, $num_trials_per_seq * $num_seq_per_exp)); ?></td><td><?php echo rounded_or_na($sum_C_avgs_prob_setting_na_to_zero); ?></td><td><?php /*same formula as for above row since we scale denominator and numerator by the same factor*/echo (($per_seq_summary_stats['sum_C_avgs_for_defined_diffs_prob'] !=/*Not !== because might be a float*/ 0 && $per_seq_summary_stats['sum_C_avgs_for_defined_diffs_prob'] !== 'n/a') ? rounded_or_na($per_seq_summary_stats['sum_diff_avgs_prob'] / $per_seq_summary_stats['sum_C_avgs_for_defined_diffs_prob'] * 100).'%' : 'n/a'); ?></td></tr>
<?php
	if ($do_wackermann) {
?>
<tr><td>Wackermann's formula<?php echo $wackerman_label_appendage; ?></td><td><?php echo rounded_or_na($wackermann_diff); ?></td><td><?php if (!$are_sampling) echo rounded_or_na($per_seq_summary_stats['sum_C_avgs_for_defined_diffs_prob']); ?></td><td><?php if (!$are_sampling) echo ($per_seq_summary_stats['sum_C_avgs_for_defined_diffs_prob'] !=/*Not !== because might be a float*/ 0 && $per_seq_summary_stats['sum_C_avgs_for_defined_diffs_prob'] !== 'n/a') ? rounded_or_na($wackermann_diff/$per_seq_summary_stats['sum_C_avgs_for_defined_diffs_prob']*100).'%' : 'n/a'; ?></td></tr>
<?php
	}
?>
<tr><td>Difference of per-<?php echo $lbl_seq_or_exp; ?> averages (excluding n/a's)</td><td><?php echo rounded_or_na($per_seq_diff_sum_avgs_prob); ?></td><td><?php echo rounded_or_na($per_seq_summary_stats['sum_C_avgs_prob']); ?></td><td><?php echo ($per_seq_summary_stats['sum_C_avgs_prob'] != 0 ? rounded_or_na($per_seq_diff_sum_avgs_prob / $per_seq_summary_stats['sum_C_avgs_prob'] * 100).'%' : 'n/a'); ?></td></tr>
<tr><td>Weighted averages (excluding n/a's)</td><td><?php echo rounded_or_na($diff_weighted_avgs_unweighted); ?></td><td><?php echo rounded_or_na($C_weighted_avgs_unweighted); ?></td><td><?php echo ($C_weighted_avgs_unweighted != 0 && $C_weighted_avgs_unweighted !== 'n/a' && $diff_weighted_avgs_unweighted !== 'n/a') ? rounded_or_na($diff_weighted_avgs_unweighted/$C_weighted_avgs_unweighted*100).'%' : 'n/a'; ?></td></tr>
</table>

<?php
}
?>

<h2>Settings</h2>

<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">

<p>Note: with the number of sequences per experiment set to a value other than one, the results should be interpreted as an outcome of pooled averaging i.e. averages (and sums) are calculated across all sequences in an experiment. With this value set to one, the results can be interpreted either as an outcome of the same i.e. pooled averaging for an experiment of one sequence per experiment, <i>or</i> as an outcome of per-sequence averaging for an experiment of an arbitrary number of sequences - these outcomes are identical, however because the latter is the more useful interpretation, it is the one this script uses i.e. labels are in terms of "sequences" rather than "experiments", and Wackermann's formula is applied.</p>

<table>
<tr>
	<td><label for="id.num_seq_per_exp">Number of sequences per experiment.</label></td>
	<td><input type="text" id="id.num_seq_per_exp" name="num_seq_per_exp" value="<?php echo $num_seq_per_exp; ?>"></td>
</tr>
<tr>
	<td><label for="id.num_trials_per_seq">Number of trials per sequence:</label></td>
	<td><input type="text" id="id.num_trials_per_seq" name="num_trials_per_seq" value="<?php echo $num_trials_per_seq; ?>"></td>
</tr>
<tr>
	<td><label for="id.reset_arousal">Reset arousal level:</label></td>
	<td><input type="text" id="id.reset_arousal" name="reset_arousal" value="<?php echo $reset_arousal; ?>"></td>
</tr>
<tr>
	<td><label for="id.arousal_inc">Arousal increment:</label></td>
	<td><input type="text" id="id.arousal_inc" name="arousal_inc" value="<?php echo $arousal_inc; ?>"></td>
</tr>
<tr>
	<td><label for="id.prob_e">Probability of occurrence of an emotional trial (inclusively between 0 and 1):</label></td>
	<td><input type="text" id="id.prob_e" name="prob_e" value="<?php echo $prob_e; ?>"></td>
</tr>
<tr>
	<td><label for="id.rounding_precision">Number of decimal places to round to (for display only):</label></td>
	<td><input type="text" id="id.rounding_precision" name="rounding_precision" value="<?php echo $rounding_precision; ?>"></td>
</tr>
</table>
<label for="id.sample_inc">Within experiments, sample every </label> <input type="text" id="id.sample_inc" name="sample_inc" value="<?php echo $sample_inc; ?>">
<label for="id.sample_inc">trials, starting from (leftmost) trial number</label> <input type="text" id="id.sample_start" name="sample_start" value="<?php echo $sample_start; ?>"><br />
<input type="checkbox" id="id.show_details" name="show_details"<?php if ($show_details) echo ' checked="checked"'; ?>><label for="id.show_details">Show per-sequence or per-experiment details, and the tables listing each of the per-sequence or per-experiment averages and sums associated with each sequence for each number of calm/emotional trials. <b>Warning</b>: <i>this will result in many rows of sequences for even relatively small numbers of trials</i>: 2 to the power of the number of trials per experiment. This is e.g. 1024 rows of experiments when you enter 10 trials per experiment, and about a million rows when you enter 20 trials per experiment.</label><br />
<input type="submit" name="submit" value="Go">
</form>

</body>

</html>

<?php

/* $seq might in fact represent multiple sequences over an experiment of $num_seq_per_exp sequences. */
function format_seq($seq) {
	global $num_trials_per_seq;

	$sequences = str_split($seq, $num_trials_per_seq);
	return implode($sequences, '&nbsp;|&nbsp;');
}

function diff_or_na($a, $b) {
	return ($a === 'n/a' || $b === 'n/a') ? 'n/a' : ($a - $b);
}

function calc_wackermann_diff($num_trials, $prob_e) {
	$ret = 0;
	$prob_c = 1 - $prob_e;
	$curr_comb_numer_factor = $num_trials + 1;
	$comb_numer = $curr_comb_numer_factor;
	$curr_comb_denom_factor = 1;
	$comb_denom = $curr_comb_denom_factor;

	for ($k = 1; $k <= $num_trials - 1; $k++) {
		$curr_comb_numer_factor--;
		$curr_comb_denom_factor++;
		$comb_numer *= $curr_comb_numer_factor;
		$comb_denom *= $curr_comb_denom_factor;
		$combinatoric = $comb_numer/$comb_denom;
		$prob = pow($prob_c, $num_trials - $k) * pow($prob_e, $k);
		$ret += $prob * $combinatoric / ($k + 2);
	}
	
	return $ret;
}

function get_avgs($stats, $items) {
	$ret = array();
	foreach ($items as $item) {
		$ret['avg_'.$item] = $stats['count_'.$item] === 0 ? 'n/a' : $stats['sum_'.$item]/$stats['count_'.$item];
	}

	return $ret;
}

function rounded_or_na($number) {
	global $rounding_precision;
	return $number === 'n/a' ? $number : round($number, $rounding_precision);
}

function get_rounded_string_list($list, $factor = 1) {
	return implode(array_map('rounded_or_na', array_map(function($item) use($factor) {return $item === 'n/a' ? 'n/a' : $item * $factor;}, $list)), ',&nbsp;&nbsp; ');
}

function calc_stats_r($seq_part, $depth, $num_trials_per_exp, &$per_seq_stats, &$per_num_calm_stats, &$per_seq_summary_stats) {
	foreach (array('C', 'E') as $trial_type) {
		if ($depth == $num_trials_per_exp - 1) {
			add_seq($seq_part.$trial_type, $per_seq_stats, $per_num_calm_stats, $per_seq_summary_stats);
		} else	calc_stats_r($seq_part.$trial_type, $depth+1, $num_trials_per_exp, $per_seq_stats, $per_num_calm_stats, $per_seq_summary_stats);
	}
}

/* $sequence represents an experiment of $num_seq_per_exp sequences of $num_trials_per_seq trials */
function add_seq($sequence, &$per_seq_stats, &$per_num_calm_stats, &$per_seq_summary_stats) {
	global $reset_arousal, $arousal_inc, $sample_start, $sample_inc, $prob_e, $num_trials_per_seq;

	$counts = $sampled_counts = $sampled_sums = array('C' => 0, 'E' => 0);
	$arousal = $reset_arousal;
	$trial_num = 1;
	foreach (str_split($sequence) as $trial_type) {
		$counts[$trial_type]++;
		if ($trial_num >= $sample_start && ($trial_num - $sample_start) % $sample_inc == 0) {
			$sampled_sums[$trial_type] += $arousal;
			$sampled_counts[$trial_type]++;
		}
		if ($trial_type == 'E' || $trial_num % $num_trials_per_seq == 0) {
			$arousal = $reset_arousal;
		} else	$arousal += $arousal_inc;
		$trial_num++;
	}
	$avgs = array(
		'C' => ($sampled_counts['C'] === 0 ? 'n/a' : $sampled_sums['C']/$sampled_counts['C']),
		'E' => ($sampled_counts['E'] === 0 ? 'n/a' : $sampled_sums['E']/$sampled_counts['E']),
	);
	$sums = array(
		'C' => $sampled_sums['C'],
		'E' => $sampled_sums['E'],
	);
	$prob = pow($prob_e, $counts['E']) * pow(1 - $prob_e, $counts['C']);
	$diff_avgs = ($avgs['C'] === 'n/a' || $avgs['E'] === 'n/a') ? 'n/a' : $avgs['E'] - $avgs['C'];
	$diff_sums = $sums['E'] - $sums['C'];
	$per_seq_stats[$sequence] = array(
		'avgs'      => $avgs,
		'sums'      => $sums,
		'diff_avgs' => $diff_avgs,
		'diff_sums' => $diff_sums,
		'prob'      => $prob,
	);

	$per_seq_summary_stats['sum_prob'] += $prob;
	$per_seq_summary_stats['count_prob']++;
	if ($avgs['C'] !== 'n/a') {
		$per_seq_summary_stats['sum_C_avgs'] += $avgs['C'];
		$per_seq_summary_stats['count_C_avgs']++;
		$per_seq_summary_stats['sum_C_avgs_prob'] += $avgs['C'] * $prob;
		$per_seq_summary_stats['count_C_avgs_prob']++;
	}
	if ($avgs['E'] !== 'n/a') {
		$per_seq_summary_stats['sum_E_avgs'] += $avgs['E'];
		$per_seq_summary_stats['count_E_avgs']++;
		$per_seq_summary_stats['sum_E_avgs_prob'] += $avgs['E'] * $prob;
		$per_seq_summary_stats['count_E_avgs_prob']++;
	}
	$per_seq_summary_stats['sum_C_sums'] += $sums['C'];
	$per_seq_summary_stats['count_C_sums']++;
	$per_seq_summary_stats['sum_E_sums'] += $sums['E'];
	$per_seq_summary_stats['count_E_sums']++;
	$per_seq_summary_stats['sum_C_sums_prob'] += $sums['C'] * $prob;
	$per_seq_summary_stats['count_C_sums_prob']++;
	$per_seq_summary_stats['sum_E_sums_prob'] += $sums['E'] * $prob;
	$per_seq_summary_stats['count_E_sums_prob']++;
	if ($diff_avgs !== 'n/a') {
		$per_seq_summary_stats['sum_diff_avgs'] += $diff_avgs;
		$per_seq_summary_stats['count_diff_avgs']++;
		$per_seq_summary_stats['sum_C_avgs_for_defined_diffs'] += $avgs['C'];
		$per_seq_summary_stats['count_C_avgs_for_defined_diffs']++;
		$per_seq_summary_stats['sum_diff_avgs_prob'] += $diff_avgs * $prob;
		$per_seq_summary_stats['count_diff_avgs_prob']++;
		$per_seq_summary_stats['sum_C_avgs_for_defined_diffs_prob'] += $avgs['C'] * $prob;
		$per_seq_summary_stats['count_C_avgs_for_defined_diffs_prob']++;
	}
	$per_seq_summary_stats['sum_diff_sums'] += $diff_sums;
	$per_seq_summary_stats['count_diff_sums']++;
	$per_seq_summary_stats['sum_diff_sums_prob'] += $diff_sums * $prob;
	$per_seq_summary_stats['count_diff_sums_prob']++;

	if (!isset($per_num_calm_stats[$counts['C']])) {
		$per_num_calm_stats[$counts['C']] = array(
			'C_avgs'     => array(),
			'E_avgs'     => array(),
			'C_sums'     => array(),
			'E_sums'     => array(),
			'diffs_avgs' => array(),
			'diffs_sums' => array(),
			'prob'       => $prob  ,
		);
	}
	$per_num_calm_stats[$counts['C']]['C_avgs'][] = $avgs['C'];
	$per_num_calm_stats[$counts['C']]['E_avgs'][] = $avgs['E'];
	$per_num_calm_stats[$counts['C']]['C_sums'][] = $sampled_sums['C'];
	$per_num_calm_stats[$counts['C']]['E_sums'][] = $sampled_sums['E'];
	$per_num_calm_stats[$counts['C']]['diffs_avgs'][] = $diff_avgs;
	$per_num_calm_stats[$counts['C']]['diffs_sums'][] = $diff_sums;
}

function calc_final_per_num_calm_stats(&$per_num_calm_stats, &$per_num_calm_summary_stats) {
	$per_num_calm_summary_stats = array(
		'sum_C_avg_avgs'            => 0,
		'sum_E_avg_avgs'            => 0,
		'count_C_avg_avgs'          => 0,
		'count_E_avg_avgs'          => 0,
		'sum_C_weighted_avg_avgs'   => 0,
		'sum_E_weighted_avg_avgs'   => 0,
		'count_C_weighted_avg_avgs' => 0,
		'count_E_weighted_avg_avgs' => 0,
		'sum_C_sum_avgs'            => 0,
		'sum_E_sum_avgs'            => 0,
		'count_C_sum_avgs'          => 0,
		'count_E_sum_avgs'          => 0,
		'sum_C_weighted_sum_avgs'   => 0,
		'sum_E_weighted_sum_avgs'   => 0,
		'count_C_weighted_sum_avgs' => 0,
		'count_E_weighted_sum_avgs' => 0,
		'sum_C_avg_sums'            => 0,
		'sum_E_avg_sums'            => 0,
		'count_C_avg_sums'          => 0,
		'count_E_avg_sums'          => 0,
		'sum_C_sum_sums'            => 0,
		'sum_E_sum_sums'            => 0,
		'count_C_sum_sums'          => 0,
		'count_E_sum_sums'          => 0,
		'sum_C_weighted_avg_sums'   => 0,
		'sum_E_weighted_avg_sums'   => 0,
		'count_C_weighted_avg_sums' => 0,
		'count_E_weighted_avg_sums' => 0,
		'sum_C_weighted_sum_sums'   => 0,
		'sum_E_weighted_sum_sums'   => 0,
		'count_C_weighted_sum_sums' => 0,
		'count_E_weighted_sum_sums' => 0,
		'sum_prob'                  => 0,
		'count_prob'                => 0,
		'sum_#seq'                  => 0,
		'count_#seq'                => 0,
		'sum_#seq_prob'             => 0,
		'count_#seq_prob'           => 0,
		'sum_#seq_for_C_weighted_avg_avgs' => 0,
		'sum_#seq_for_E_weighted_avg_avgs' => 0,
	);

	foreach ($per_num_calm_stats as &$stats) {
		foreach (array('C', 'E') as $trial_type) {
			$sum_avgs = $sum_sums = $count_avgs = $count_sums = 0;
			foreach ($stats[$trial_type.'_avgs'] as $avg) {
				if ($avg !== 'n/a') {
					$count_avgs++;
					$sum_avgs += $avg;
				}
			}
			foreach ($stats[$trial_type.'_sums'] as $sum) {
				$count_sums++;
				$sum_sums += $sum;
			}
			$stats[$trial_type.'_avg_avgs'] = ($count_avgs === 0 ? 'n/a' : $sum_avgs/$count_avgs);
			$stats[$trial_type.'_weighted_avg_avgs'] = ($count_avgs === 0 ? 'n/a' : $sum_avgs/$count_avgs*count($stats[$trial_type.'_avgs'])*$stats['prob']);
			$stats[$trial_type.'_sum_avgs'] = $sum_avgs;
			$stats[$trial_type.'_avg_sums'] = ($count_avgs === 0 ? 'n/a' : $sum_sums/$count_sums);
			$stats[$trial_type.'_sum_sums'] = $sum_sums;
			assert(!isset($stats['#seq']) || $stats['#seq'] == count($stats[$trial_type.'_avgs']));
			$add_num_seq = !isset($stats['#seq']);
			$stats['#seq'] = count($stats[$trial_type.'_avgs']);

			if ($stats[$trial_type.'_avg_avgs'] !== 'n/a') {
				$per_num_calm_summary_stats['sum_'.$trial_type.'_avg_avgs'] += $stats[$trial_type.'_avg_avgs'];
				$per_num_calm_summary_stats['count_'.$trial_type.'_avg_avgs']++;
				$per_num_calm_summary_stats['sum_#seq_for_'.$trial_type.'_weighted_avg_avgs'] += $stats['#seq'];
			}
			if ($stats[$trial_type.'_weighted_avg_avgs'] !== 'n/a') {
				$per_num_calm_summary_stats['sum_'.$trial_type.'_weighted_avg_avgs'] += $stats[$trial_type.'_weighted_avg_avgs'];
				$per_num_calm_summary_stats['count_'.$trial_type.'_weighted_avg_avgs']++;
			}
			$per_num_calm_summary_stats['sum_'.$trial_type.'_sum_avgs'] += $stats[$trial_type.'_sum_avgs'];
			$per_num_calm_summary_stats['count_'.$trial_type.'_sum_avgs']++;
			$per_num_calm_summary_stats['sum_'.$trial_type.'_weighted_sum_avgs'] += $stats[$trial_type.'_sum_avgs'] * $stats['prob'];
			$per_num_calm_summary_stats['count_'.$trial_type.'_weighted_sum_avgs']++;
			if ($stats[$trial_type.'_avg_sums'] !== 'n/a') {
				$per_num_calm_summary_stats['sum_'.$trial_type.'_avg_sums'] += $stats[$trial_type.'_avg_sums'];
				$per_num_calm_summary_stats['count_'.$trial_type.'_avg_sums']++;
				$per_num_calm_summary_stats['sum_'.$trial_type.'_weighted_avg_sums'] += $stats[$trial_type.'_avg_sums'] * $stats['prob'] * $stats['#seq'];
				$per_num_calm_summary_stats['count_'.$trial_type.'_weighted_avg_sums']++;
			}
			$per_num_calm_summary_stats['sum_'.$trial_type.'_sum_sums'] += $stats[$trial_type.'_sum_sums'];
			$per_num_calm_summary_stats['count_'.$trial_type.'_sum_sums']++;
			$per_num_calm_summary_stats['sum_'.$trial_type.'_weighted_sum_sums'] += $stats[$trial_type.'_sum_sums'] * $stats['prob'];
			$per_num_calm_summary_stats['count_'.$trial_type.'_weighted_sum_sums']++;
			if ($add_num_seq) {
				$per_num_calm_summary_stats['sum_prob'] += $stats['prob'];
				$per_num_calm_summary_stats['count_prob']++;
				$per_num_calm_summary_stats['sum_#seq'] += $stats['#seq'];
				$per_num_calm_summary_stats['count_#seq']++;
				$per_num_calm_summary_stats['sum_#seq_prob'] += $stats['#seq']*$stats['prob'];
				$per_num_calm_summary_stats['count_#seq_prob']++;
			}
		}
	}
}

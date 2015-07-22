<?php

/**
 * A lightweight PHP web script which I used to generate the table in this post to the Skeptiko forum:
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
 * To run the script, simply place it on a PHP-capable web server, and browse to it.
 *
 * I release this code into the public domain with the caveats that I:
 *
 * 1. Assert original authorship of this code, and the moral and legal right to be identified as its original author.
 * 2. Deny anybody else the right to falsely claim authorship over it other than of any changes that they make to it.
 *
 * Author : Laird Shaw.
 * Date   : 2015-07-22.
 * Version: 3.
 *
 * Changelog:
 *
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
<title>Examining theoretical expectation bias phenomena in presentiment experiments with respect to number of calm trials in each sequence</title>
<style type="text/css">
.tbl_data {
	border-collapse: collapse;
}
.tbl_data td, .tbl_data th {
	text-align: center;
	border: 1px solid black;
	padding: 3px;
}
</style>
</head>

<body>

<h2>Examining theoretical expectation bias phenomena in presentiment experiments with respect to number of calm trials in each sequence</h2>
	
<?php
if (!isset($_GET['num_trials'])) {
?>

<h2>Enter settings</h2>

<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<table>
<tr>
	<td><label for="id.num_trials">Number of trials per sequence:</label></td>
	<td><input type="text" id="id.num_trials" name="num_trials" value="7"></td>
</tr>
<tr>
	<td><label for="id.reset_arousal">Reset arousal level:</label></td>
	<td><input type="text" id="id.reset_arousal" name="reset_arousal" value="0"></td>
</tr>
<tr>
	<td><label for="id.arousal_inc">Arousal increment:</label></td>
	<td><input type="text" id="id.arousal_inc" name="arousal_inc" value="1"></td>
</tr>
<tr>
	<td><label for="id.rounding_precision">Number of decimal places to round to (for display only):</label></td>
	<td><input type="text" id="id.rounding_precision" name="rounding_precision" value="6"></td>
</tr>
</table>
<label for="id.sample_inc">Within sequences, sample every </label> <input type="text" id="id.sample_inc" name="sample_inc" value="1">
<label for="id.sample_inc">trials, starting from (leftmost) trial number</label> <input type="text" id="id.sample_start" name="sample_start" value="1"><br />
<input type="checkbox" id="id.show_details" name="show_details"><label for="id.show_details">Show details including averages associated with each sequence, and lists of averages associated with each number of calm trials. <b>Warning</b>: <i>this will result in many rows of sequences for even relatively small numbers of trials</i>: 2 to the power of the value you enter above for number of trials per sequence. This is e.g. 1024 rows of sequences when you enter 10 trials per sequence, and about a million rows when you enter 20 trials per sequence.</label><br />
<input type="submit" name="submit" value="Go">
</form>
<?php
} else {
	$prob_e = 0.5;
	$per_seq_stats = $per_num_calm_stats = $per_num_calm_summary_stats = array();
	$per_seq_summary_stats = array(
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
	);
	$num_trials = (int)$_GET['num_trials'];
	if ($num_trials < 1) $num_trials = 1;
	$reset_arousal = (float)$_GET['reset_arousal'];
	$arousal_inc = (float)$_GET['arousal_inc'];
	$rounding_precision = (int)$_GET['rounding_precision'];
	$sample_start = (int)$_GET['sample_start'];
	if ($sample_start < 1) $sample_start = 1;
	$sample_inc = (int)$_GET['sample_inc'];
	if ($sample_inc < 1) $sample_inc = 1;
	$show_details = isset($_GET['show_details']);
	calc_stats_r('', 0, $num_trials, $per_seq_stats, $per_num_calm_stats, $per_seq_summary_stats);
	calc_final_per_num_calm_stats($per_num_calm_stats, $per_num_calm_summary_stats);
	$per_seq_avgs = get_avgs($per_seq_summary_stats, array('C_avgs', 'E_avgs', 'diff_avgs', 'C_sums', 'E_sums', 'diff_sums', 'C_avgs_for_defined_diffs'));
	$per_seq_diff_avg_avgs = ($per_seq_avgs['avg_C_avgs'] !== 'n/a' && $per_seq_avgs['avg_E_avgs'] !== 'n/a' ? rounded_or_na($per_seq_avgs['avg_E_avgs'] - $per_seq_avgs['avg_C_avgs']) : 'n/a');
	$per_seq_diff_avg_sums = ($per_seq_avgs['avg_C_sums'] !== 'n/a' && $per_seq_avgs['avg_E_sums'] !== 'n/a' ? rounded_or_na($per_seq_avgs['avg_E_sums'] - $per_seq_avgs['avg_C_sums']) : 'n/a');
	$num_calm_avgs = get_avgs($per_num_calm_summary_stats, array('C_avg_avgs', 'E_avg_avgs', 'C_weighted_avg_avgs', 'E_weighted_avg_avgs', 'C_sum_avgs', 'E_sum_avgs', 'C_avg_sums', 'E_avg_sums', 'C_sum_sums', 'E_sum_sums', '#seq'));

?>

<p>Number of trials per sequence: <?php echo $num_trials; ?>.</p>

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
<h2>Per-sequence arousals</h2>

<table class="tbl_data">
<tr><th>Sequence</th><th>Average arousal (C)</th><th>Average arousal (E)</th><th>Difference of averages</th><th>Sum of arousals (C)</th><th>Sum of arousals (E)</th><th>Difference of sums</th></tr>
<?php
		foreach ($per_seq_stats as $seq => $seq_stats) {
			echo "<tr><td>$seq</td><td>".rounded_or_na($seq_stats['avgs']['C'])."</td><td>".rounded_or_na($seq_stats['avgs']['E'])."</td><td>".rounded_or_na($seq_stats['diff_avgs'])."</td><td>".rounded_or_na($seq_stats['sums']['C'])."</td><td>".rounded_or_na($seq_stats['sums']['E'])."</td><td>".rounded_or_na($seq_stats['diff_sums'])."</td></tr>\n";
		}
		echo '<tr><th>Totals:</th><th>'.rounded_or_na($per_seq_summary_stats['sum_C_avgs']).'<th>'.rounded_or_na($per_seq_summary_stats['sum_E_avgs']).'</th><th>'.rounded_or_na($per_seq_summary_stats['sum_diff_avgs']).'</th><th>'.rounded_or_na($per_seq_summary_stats['sum_C_sums']).'</th><th>'.rounded_or_na($per_seq_summary_stats['sum_E_sums']).'</th><th>'.rounded_or_na($per_seq_summary_stats['sum_diff_sums']).'</th></tr>'."\n";
		echo '<tr><th>Averages:</th><th>'.rounded_or_na($per_seq_avgs['avg_C_avgs']).'<th>'.rounded_or_na($per_seq_avgs['avg_E_avgs']).'</th><th>'.rounded_or_na($per_seq_avgs['avg_diff_avgs']).'</th><th>'.rounded_or_na($per_seq_avgs['avg_C_sums']).'</th><th>'.rounded_or_na($per_seq_avgs['avg_E_sums']).'</th><th>'.rounded_or_na($per_seq_avgs['avg_diff_sums']	).'</th></tr>'."\n";
		
		echo '<tr><th>Differences:</th><th colspan="2">'.$per_seq_diff_avg_avgs.'</th><th></th><th colspan="2">'.$per_seq_diff_avg_sums.'</th><th></th></tr>'."\n";
?>
</table>

<h2>Per-sequence average arousals for given numbers of calm trials</h2>

<p>As extracted from the above per-sequence table.</p>

<table class="tbl_data">
<tr><th>#C</th><th>#E</th><th>Average Arousals (C)</th><th>Average Arousals (E)</th></tr>
<?php
		foreach ($per_num_calm_stats as $num_calm => $stats) {
			echo "<tr><td>$num_calm</td><td>".($num_trials - $num_calm)."</td><td>".get_rounded_string_list($stats['C_avgs'])."</td><td>".get_rounded_string_list($stats['E_avgs']).'</td></tr>'."\n";
		}
?>
</table>

<h2>Per-sequence sums of arousals for given numbers of calm trials</h2>

<p>As extracted from the above per-sequence table.</p>

<table class="tbl_data">
<tr><th>#C</th><th>#E</th><th>Sums of Arousals (C)</th><th>Sums of Arousals (E)</th></tr>
<?php
		foreach ($per_num_calm_stats as $num_calm => $stats) {
			echo "<tr><td>$num_calm</td><td>".($num_trials - $num_calm)."</td><td>".get_rounded_string_list($stats['C_sums'])."</td><td>".get_rounded_string_list($stats['E_sums']).'</td></tr>'."\n";
		}
?>
</table>

<?php
	}
?>

<h2>Sum and average of per-sequence averages and sums of arousals for given numbers of calm/emotional trials</h2>

<?php
	if ($show_details) {
		echo '<p>As derived from the above two tables.</p>'."\n";
	}
?>

<table class="tbl_data">
<tr><th>#C</th><th>#E</th><th>#sequences</th><th>Average of per-sequence average arousals (C)</th><th>Average of per-sequence average arousals (E)</th><th>Average of per-sequence average arousals (C) weighted by #sequences</th><th>Average of per-sequence average arousals (E) weighted by #sequences</th><th>Sum of per-sequence average arousals (C)</th><th>Sum of per-sequence average arousals (E)</th><th>Average of per-sequence sums of arousal (C)</th><th>Average of per-sequence sums of arousal (E)</th><th>Sum of per-sequence sums of arousal (C)</th><th>Sum of per-sequence sums of arousal (E)</th></tr>
<?php
	foreach ($per_num_calm_stats as $num_calm => $stats) {
		echo "<tr><td>$num_calm</td><td>".($num_trials - $num_calm)."</td><td>{$stats['#seq']}</td><td>".rounded_or_na($stats['C_avg_avgs'])."</td><td>".rounded_or_na($stats['E_avg_avgs'])."</td><td>".rounded_or_na($stats['C_weighted_avg_avgs'])."</td><td>".rounded_or_na($stats['E_weighted_avg_avgs'])."</td><td>".rounded_or_na($stats['C_sum_avgs'])."</td><td>".rounded_or_na($stats['E_sum_avgs'])."</td><td>".rounded_or_na($stats['C_avg_sums'])."</td><td>".rounded_or_na($stats['E_avg_sums'])."</td><td>".rounded_or_na($stats['C_sum_sums'])."</td><td>".rounded_or_na($stats['E_sum_sums'])."</td></tr>\n";
	}
?>
<tr><th>Totals:</th><th></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_#seq']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_C_avg_avgs']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_avg_avgs']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_C_weighted_avg_avgs']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_weighted_avg_avgs']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_C_sum_avgs']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_sum_avgs']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_C_avg_sums']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_avg_sums']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_C_sum_sums']); ?></th><th><?php echo rounded_or_na($per_num_calm_summary_stats['sum_E_sum_sums']); ?></th></tr>
<tr><th>Averages:</th><th></th><th><?php echo rounded_or_na($num_calm_avgs['avg_#seq']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_C_avg_avgs']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_E_avg_avgs']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_C_weighted_avg_avgs']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_E_weighted_avg_avgs']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_C_sum_avgs']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_E_sum_avgs']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_C_avg_sums']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_E_avg_sums']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_C_sum_sums']); ?></th><th><?php echo rounded_or_na($num_calm_avgs['avg_E_sum_sums']); ?></th></tr>
</table>

<h2>Expectation bias</h2>

<?php

$C_weighted_avgs_unweighted = $per_num_calm_summary_stats['sum_#seq_for_C_weighted_avg_avgs'] !== 0 ? $per_num_calm_summary_stats['sum_C_weighted_avg_avgs']/$per_num_calm_summary_stats['sum_#seq_for_C_weighted_avg_avgs'] : 'n/a';
$diff_weighted_avgs_unweighted = ($per_num_calm_summary_stats['sum_#seq_for_C_weighted_avg_avgs'] !== 0 && $per_num_calm_summary_stats['sum_#seq_for_E_weighted_avg_avgs'] !== 0) ? ($per_num_calm_summary_stats['sum_E_weighted_avg_avgs']/$per_num_calm_summary_stats['sum_#seq_for_E_weighted_avg_avgs'] - $C_weighted_avgs_unweighted) : 'n/a';
$avg_C_avgs_setting_na_to_zero = $per_seq_summary_stats['count_C_avgs_for_defined_diffs'] !== 0 ? $per_seq_avgs['avg_C_avgs_for_defined_diffs'] * $per_seq_summary_stats['count_C_avgs_for_defined_diffs'] / pow(2, $num_trials) : 0;

$scale_wackermann = ($arousal_inc != 1);
$are_sampling = !($sample_start == 1 && $sample_inc == 1);
$wackermann_diff = calc_wackermann_diff($num_trials, $prob_e);
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
?>

<table class="tbl_data">
<tr><th>Method</th><th>Average bias in per-sequence average arousals for emotional versus calm trials</th><th>Average of per-sequence average arousals for calm trials</th><th>Expectation bias as a percentage of average calm arousal</th></tr>
<tr><td>Average of per sequence differences (excluding n/a's)</td><td><?php echo rounded_or_na($per_seq_avgs['avg_diff_avgs']); ?></td><td><?php echo rounded_or_na($per_seq_avgs['avg_C_avgs_for_defined_diffs']); ?></td><td><?php echo (($per_seq_avgs['avg_C_avgs_for_defined_diffs'] !== 0 && $per_seq_avgs['avg_diff_avgs'] !== 'n/a') ? rounded_or_na($per_seq_avgs['avg_diff_avgs'] / $per_seq_avgs['avg_C_avgs_for_defined_diffs'] * 100).'%' : 'n/a'); ?></td></tr>
<tr><td>Average of per sequence differences (setting n/a's to zero)</td><td><?php echo $per_seq_avgs['avg_diff_avgs'] !== 'n/a' ? rounded_or_na($per_seq_avgs['avg_diff_avgs'] * $per_seq_summary_stats['count_diff_avgs'] / pow(2, $num_trials)) : 0; ?></td><td><?php echo rounded_or_na($avg_C_avgs_setting_na_to_zero); ?></td><td><?php /*same formula as for above row since we scale denominator and numerator by the same factor*/echo ($per_seq_avgs['avg_C_avgs_for_defined_diffs'] != 0 ? rounded_or_na($per_seq_avgs['avg_diff_avgs'] / $per_seq_avgs['avg_C_avgs_for_defined_diffs'] * 100).'%' : '0%'); ?></td></tr>
<tr><td>Difference of per sequence averages (excluding n/a's)</td><td><?php echo rounded_or_na($per_seq_diff_avg_avgs); ?></td><td><?php echo rounded_or_na($per_seq_avgs['avg_C_avgs']); ?></td><td><?php echo ($per_seq_avgs['avg_C_avgs'] !== 0 ? rounded_or_na($per_seq_diff_avg_avgs / $per_seq_avgs['avg_C_avgs'] * 100).'%' : 'n/a'); ?></td></tr>
<tr><td>Weighted averages (excluding n/a's)</td><td><?php echo rounded_or_na($diff_weighted_avgs_unweighted); ?></td><td><?php echo rounded_or_na($C_weighted_avgs_unweighted); ?></td><td><?php echo ($C_weighted_avgs_unweighted !== 'n/a' && $diff_weighted_avgs_unweighted !== 'n/a') ? rounded_or_na($diff_weighted_avgs_unweighted/$C_weighted_avgs_unweighted*100).'%' : 'n/a'; ?></td></tr>
<tr><td>Wackermann's formula<?php echo $wackerman_label_appendage; ?></td><td><?php echo rounded_or_na($wackermann_diff); ?></td><td><?php if (!$are_sampling) echo rounded_or_na($avg_C_avgs_setting_na_to_zero); ?></td><td><?php if (!$are_sampling) echo $avg_C_avgs_setting_na_to_zero !== 'n/a' ? rounded_or_na($wackermann_diff/$avg_C_avgs_setting_na_to_zero*100).'%' : 'n/a'; ?></td></tr>
</table>

<?php
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

function get_rounded_string_list($list) {
	return implode(array_map('rounded_or_na', $list), ',&nbsp;&nbsp; ');
}

function calc_stats_r($seq_part, $depth, $num_trials, &$per_seq_stats, &$per_num_calm_stats, &$per_seq_summary_stats) {
	foreach (array('C', 'E') as $trial_type) {
		if ($depth == $num_trials - 1) {
			add_seq($seq_part.$trial_type, $per_seq_stats, $per_num_calm_stats, $per_seq_summary_stats);
		} else	calc_stats_r($seq_part.$trial_type, $depth+1, $num_trials, $per_seq_stats, $per_num_calm_stats, $per_seq_summary_stats);
	}
}

function add_seq($sequence, &$per_seq_stats, &$per_num_calm_stats, &$per_seq_summary_stats) {
	global $reset_arousal, $arousal_inc, $sample_start, $sample_inc;

	$counts = $sampled_counts = $sampled_sums = array('C' => 0, 'E' => 0);
	$arousal = $reset_arousal;
	$trial_num = 1;
	foreach (str_split($sequence) as $trial_type) {
		$counts[$trial_type]++;
		if ($trial_num >= $sample_start && ($trial_num - $sample_start) % $sample_inc == 0) {
			$sampled_sums[$trial_type] += $arousal;
			$sampled_counts[$trial_type]++;
		}
		if ($trial_type == 'C') {
			$arousal += $arousal_inc;
		} else	$arousal = $reset_arousal;
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
	$diff_avgs = ($avgs['C'] === 'n/a' || $avgs['E'] === 'n/a') ? 'n/a' : $avgs['E'] - $avgs['C'];
	$diff_sums = $sums['E'] - $sums['C'];
	$per_seq_stats[$sequence] = array(
		'avgs'      => $avgs,
		'sums'      => $sums,
		'diff_avgs' => $diff_avgs,
		'diff_sums' => $diff_sums,
	);
	
	if ($avgs['C'] !== 'n/a') {
		$per_seq_summary_stats['sum_C_avgs'] += $avgs['C'];
		$per_seq_summary_stats['count_C_avgs']++;
	}
	if ($avgs['E'] !== 'n/a') {
		$per_seq_summary_stats['sum_E_avgs'] += $avgs['E'];
		$per_seq_summary_stats['count_E_avgs']++;
	}
	$per_seq_summary_stats['sum_C_sums'] += $sums['C'];
	$per_seq_summary_stats['count_C_sums']++;
	$per_seq_summary_stats['sum_E_sums'] += $sums['E'];
	$per_seq_summary_stats['count_E_sums']++;
	if ($diff_avgs !== 'n/a') {
		$per_seq_summary_stats['sum_diff_avgs'] += $diff_avgs;
		$per_seq_summary_stats['count_diff_avgs']++;
		$per_seq_summary_stats['sum_C_avgs_for_defined_diffs'] += $avgs['C'];
		$per_seq_summary_stats['count_C_avgs_for_defined_diffs']++;
	}
	$per_seq_summary_stats['sum_diff_sums'] += $diff_sums;
	$per_seq_summary_stats['count_diff_sums']++;

	if (!isset($per_num_calm_stats[$counts['C']])) {
		$per_num_calm_stats[$counts['C']] = array(
			'C_avgs'     => array(),
			'E_avgs'     => array(),
			'C_sums'     => array(),
			'E_sums'     => array(),
			'diffs_avgs' => array(),
			'diffs_sums' => array(),
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
		'sum_C_avg_sums'            => 0,
		'sum_E_avg_sums'            => 0,
		'count_C_avg_sums'          => 0,
		'count_E_avg_sums'          => 0,
		'sum_C_sum_sums'            => 0,
		'sum_E_sum_sums'            => 0,
		'count_C_sum_sums'          => 0,
		'count_E_sum_sums'          => 0,
		'sum_#seq'                  => 0,
		'count_#seq'                => 0,
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
			$stats[$trial_type.'_weighted_avg_avgs'] = ($count_avgs === 0 ? 'n/a' : $sum_avgs/$count_avgs*count($stats[$trial_type.'_avgs']));
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
			if ($stats[$trial_type.'_avg_sums'] !== 'n/a') {
				$per_num_calm_summary_stats['sum_'.$trial_type.'_avg_sums'] += $stats[$trial_type.'_avg_sums'];
				$per_num_calm_summary_stats['count_'.$trial_type.'_avg_sums']++;
			}
			$per_num_calm_summary_stats['sum_'.$trial_type.'_sum_sums'] += $stats[$trial_type.'_sum_sums'];
			$per_num_calm_summary_stats['count_'.$trial_type.'_sum_sums']++;
			if ($add_num_seq) {
				$per_num_calm_summary_stats['sum_#seq'] += $stats['#seq'];
				$per_num_calm_summary_stats['count_#seq']++;
			}
		}
	}
}
?>
</body>

</html>
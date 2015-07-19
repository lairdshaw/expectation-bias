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
 * Date   : 2015-07-19.
 * Version: 2.
 *
 * Changelog:
 *
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
<input type="checkbox" id="id.show_details" name="show_details"><label for="id.show_details">Show details including averages associated with each sequence, and lists of averages associated with each number of calm trials. Warning: this will result in many rows of sequences for even relatively small numbers of trials: 2 to the power of the value you enter above for number of trials per sequence. This is e.g. 1024 rows of sequences when you enter 10 trials per sequence, and about a million rows when you enter 20 trials per sequence.</label><br />
<input type="submit" name="submit" value="Go">
</form>
<?php
} else {
	$avgs_per_seq = $stats_per_num_calm = array();
	$num_trials = (int)$_GET['num_trials'];
	$reset_arousal = (float)$_GET['reset_arousal'];
	$arousal_inc = (float)$_GET['arousal_inc'];
	$rounding_precision = (int)$_GET['rounding_precision'];
	$sample_start = (int)$_GET['sample_start'];
	if ($sample_start < 1) $sample_start = 1;
	$sample_inc = (int)$_GET['sample_inc'];
	if ($sample_inc <1) $sample_inc = 1;
	$show_details = isset($_GET['show_details']);
	calc_stats_r('', 0, $num_trials, $avgs_per_seq, $stats_per_num_calm);
	calc_final_per_num_calm_stats($stats_per_num_calm);
	$avg_avgs_per_seq = calc_avg_avgs_per_seq($avgs_per_seq);
	/* Sanity check to verify the above using the same data just after having been processed into sums of averages versus #sequences. If these values do not match those above when displayed (those above are displayed only if the "Show details" checkbox is checked), then there is probably a bug in the code. */
	$avg_avgs_over_num_calm = calc_avg_avgs_over_num_calm($stats_per_num_calm);

?>

<p>Number of trials per sequence: <?php echo $num_trials; ?>.</p>

<p>Reset arousal level: <?php echo $reset_arousal; ?>.</p>

<p>Arousal increment: <?php echo $arousal_inc; ?>.</p>

<p>Number of decimal places to round to: <?php echo $rounding_precision; ?>.</p>

<p>Sampling within each sequence every <?php echo $sample_inc; ?> trials beginning from (leftmost) trial <?php echo $sample_start; ?>.</p>

<p>C = calm trial (after which arousal increments).</p>

<p>E = emotional trial (after which arousal resets).</p>

<p>Arousal starts at reset level. Arousal level for a particular trial is the arousal level <i>preceding</i> that trial. Averages are with respect to these preceding arousal levels across all <i>sampled</i> calm/emotional trials in the sequence.</p> 

<?php
	if ($show_details) {
?>
<h2>Average arousals over sequences</h2>

<table class="tbl_data">
<tr><th>Sequence</th><th>Average Arousal (C)</th><th>Average Arousal (E)</th></tr>
<?php
		foreach ($avgs_per_seq as $seq => $avgs) {
			echo "<tr><td>$seq</td><td>".rounded_or_na($avgs['C'])."</td><td>".rounded_or_na($avgs['E'])."</td></tr>\n";
		}
		echo "<tr><th>Averages:</th><th>".rounded_or_na($avg_avgs_per_seq['C'])."</th><th>".rounded_or_na($avg_avgs_per_seq['E'])."</th></tr>\n";
?>
</table>
<?php
		echo "Expectation bias: ".rounded_or_na(($avg_avgs_per_seq['E'] - $avg_avgs_per_seq['C'])/$avg_avgs_per_seq['C'] * 100)."%\n";
?>

<h2>Average arousals over sequences for given numbers of calm trials</h2>

<p>As extracted from the above table.</p>

<table class="tbl_data">
<tr><th>#C</th><th>Average Arousals (C)</th><th>Average Arousals (E)</th></tr>
<?php
		foreach ($stats_per_num_calm as $num_calm => $stats) {
			echo "<tr><td>$num_calm</td><td>".get_rounded_string_list($stats['C_avgs'])."</td><td>".get_rounded_string_list($stats['E_avgs'])."</td></tr>\n";
		}
?>
</table>
<?php
	}
?>

<h2>Sum and average of average arousals over sequences for given numbers of calm trials</h2>

<?php
	if ($show_details) {
		echo '<p>As derived from the above table.</p>'."\n";
	}
?>

<table class="tbl_data">
<tr><th>#C</th><th>Average Average Arousal (C)</th><th>Average Average Arousal (E)</th><th>#sequences</th><th>Sum of Average Arousal (C)</th><th>Sum of Average Arousal (E)</th></tr>
<?php
	foreach ($stats_per_num_calm as $num_calm => $stats) {
		echo "<tr><td>$num_calm</td><td>".rounded_or_na($stats['C_avg_avgs'])."</td><td>".rounded_or_na($stats['E_avg_avgs'])."</td><td>{$stats['#seq']}</td><td>".rounded_or_na($stats['C_sum_avgs'])."</td><td>".rounded_or_na($stats['E_sum_avgs'])."</td></tr>\n";
	}
?>
</table>

<?php

	echo "Avg(C): ".rounded_or_na($avg_avgs_over_num_calm['C'])."<br />\n";
	echo "Avg(E): ".rounded_or_na($avg_avgs_over_num_calm['E'])."<br />\n";
	echo "Expectation bias: ".rounded_or_na(($avg_avgs_over_num_calm['E'] - $avg_avgs_over_num_calm['C'])/$avg_avgs_over_num_calm['C'] * 100)."%\n";
}

function calc_avg_avgs_per_seq($avgs_per_seq) {
	$ret = array();
	$sums = $counts = array('C' => 0, 'E' => 0);
	foreach ($avgs_per_seq as $avgs) {
		foreach (array('C', 'E') as $trial_type) {
			if ($avgs[$trial_type] !== 'n/a') {
				$sums[$trial_type] += $avgs[$trial_type];
				$counts[$trial_type]++;
			}
		}
	}
	foreach (array('C', 'E') as $trial_type) {
		$ret[$trial_type] = ($counts[$trial_type] === 0 ? 'n/a' : $sums[$trial_type]/$counts[$trial_type]);
	}

	return $ret;
}

function calc_avg_avgs_over_num_calm($stats_per_num_calm) {
	$ret = array();
	$sums = $counts = array('C' => 0, 'E' => 0);
	foreach ($stats_per_num_calm as $stats) {
		foreach (array('C', 'E') as $trial_type) {
			if ($stats[$trial_type.'_sum_avgs'] !== 'n/a') {
				$sums[$trial_type] += $stats[$trial_type.'_sum_avgs'];
				$counts[$trial_type] += $stats['#seq'];
			}
		}
	}
	foreach (array('C', 'E') as $trial_type) {
		$ret[$trial_type] = ($counts[$trial_type] === 0 ? 'n/a' : $sums[$trial_type]/$counts[$trial_type]);
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

function calc_stats_r($seq_part, $depth, $num_trials, &$avgs_per_seq, &$stats_per_num_calm) {
	foreach (array('C', 'E') as $trial_type) {
		if ($depth == $num_trials - 1) {
			add_seq($seq_part.$trial_type, $avgs_per_seq, $stats_per_num_calm);
		} else	calc_stats_r($seq_part.$trial_type, $depth+1, $num_trials, $avgs_per_seq, $stats_per_num_calm);
	}
}

function add_seq($sequence, &$avgs_per_seq, &$stats_per_num_calm) {
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
	$avgs_per_seq[$sequence] = $avgs;
	if (!isset($stats_per_num_calm[$counts['C']])) {
		$stats_per_num_calm[$counts['C']] = array(
			'C_avgs' => array(),
			'E_avgs' => array(),
		);
	}
	$stats_per_num_calm[$counts['C']]['C_avgs'][] = $avgs['C'];
	$stats_per_num_calm[$counts['C']]['E_avgs'][] = $avgs['E'];
}

function calc_final_per_num_calm_stats(&$stats_per_num_calm) {
	foreach ($stats_per_num_calm as &$stats) {
		foreach (array('C', 'E') as $trial_type) {
			$sum = $count = 0;
			foreach ($stats[$trial_type.'_avgs'] as $avg) {
				if ($avg !== 'n/a') {
					$count++;
					$sum += $avg;
				}
			}
			$stats[$trial_type.'_avg_avgs'] = ($count === 0 ? 'n/a' : $sum/$count);
			$stats[$trial_type.'_sum_avgs'] = ($count === 0 ? 'n/a' : $sum);
			assert(!isset($stats['#seq']) || $stats['#seq'] == count($stats[$trial_type.'_avgs']));
			$stats['#seq'] = count($stats[$trial_type.'_avgs']);
		}
	}
}
?>
</body>

</html>
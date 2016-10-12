<?php
if ( $is_pro ) {
	$edition = 'pro';
}
else {
	$edition = 'lite';
}

$filter_file = "$tmp_dir/filter";

`cat "$build_dir/filter-all" "$build_dir/filter-$edition" > "$filter_file"`;
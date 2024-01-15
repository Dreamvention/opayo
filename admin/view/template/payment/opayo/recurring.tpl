<div id="opayo_recurring" class="opayo-recurring">
	<?php if (($recurring_status == '2') || ($recurring_status == '3')) { ?>
	<button type="button" class="btn btn-primary button-enable-recurring"><?php echo $button_enable_recurring; ?></button>
	<?php } else { ?>
	<button type="button" class="btn btn-primary button-disable-recurring"><?php echo $button_disable_recurring; ?></button>
	<?php } ?>
</div>
<br />
<script type="text/javascript">

$('#opayo_recurring').on('click', '.button-enable-recurring', function() {
	$.ajax({
		type: 'post',
		url: '<?php echo $enable_url; ?>',
		data: {'order_recurring_id' : '<?php echo $order_recurring_id; ?>'},
		dataType: 'json',
		beforeSend: function() {
			$('#opayo_recurring .btn').prop('disabled', true);
		},
		complete: function() {
			$('#opayo_recurring .btn').prop('disabled', false);
		},
		success: function(json) {
			$('.alert-dismissible').remove();
			
			if (json['error'] && json['error']['warning']) {
				$('#content > .container-fluid').prepend('<div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> ' + json['error']['warning'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
				
				$('html, body').animate({scrollTop: $('#content > .container-fluid .alert-danger').offset().top}, 'slow');
			}
			
			if (json['success']) {
				$('#content > .container-fluid').prepend('<div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
				
				$('html, body').animate({scrollTop: $('#content > .container-fluid .alert-success').offset().top}, 'slow');
				
				$('#opayo_recurring').load('<?php echo $info_url; ?> #opayo_recurring >');
			}
		},
		error: function(xhr, ajaxOptions, thrownError) {
			console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});
});

$('#opayo_recurring').on('click', '.button-disable-recurring', function() {
	$.ajax({
		type: 'post',
		url: '<?php echo $disable_url; ?>',
		data: {'order_recurring_id' : '<?php echo $order_recurring_id; ?>'},
		dataType: 'json',
		beforeSend: function() {
			$('#opayo_recurring .btn').prop('disabled', true);
		},
		complete: function() {
			$('#opayo_recurring .btn').prop('disabled', false);
		},
		success: function(json) {
			$('.alert-dismissible').remove();
			
			if (json['error'] && json['error']['warning']) {
				$('#content > .container-fluid').prepend('<div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> ' + json['error']['warning'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
				
				$('html, body').animate({scrollTop: $('#content > .container-fluid .alert-danger').offset().top}, 'slow');
			}
			
			if (json['success']) {
				$('#content > .container-fluid').prepend('<div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
				
				$('html, body').animate({scrollTop: $('#content > .container-fluid .alert-success').offset().top}, 'slow');
				
				$('#opayo_recurring').load('<?php echo $info_url; ?> #opayo_recurring >');
			}
		},
		error: function(xhr, ajaxOptions, thrownError) {
			console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});
});

</script>
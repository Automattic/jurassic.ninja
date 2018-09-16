<img id="img1" class="aligncenter" style="display: none;" data-failure-img-src="<?php echo esc_attr( $atts['failure_image'] ); ?>" src="https://i.imgur.com/mKwWJQZ.gif" />
<img id="img2" class="aligncenter" style="display: none;" src="https://i.imgur.com/wWkoZGw.gif" />
<p id="progress" style="text-align: center;"
	data-success-message="<?php echo esc_attr( $atts['success_message'] ); ?>"
	data-error-message="<?php echo esc_attr( $atts['failure_message'] ); ?>">
	<?php echo esc_html( $atts['spinner_message'] );?>
</p>

<script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script>

<script data-keepinline="true">
	window.renderOptIn = function() {
		window.gapi.load('surveyoptin', function() {
			window.gapi.surveyoptin.render(
			{
				"merchant_id": {$merchant_id},
				"order_id": "{$order_id}",
				"email": "{$customer_email}",
				"delivery_country": "{$country}",
				"estimated_delivery_date": "{$estimated_delivery_date}"
			});
		});
	}
</script>

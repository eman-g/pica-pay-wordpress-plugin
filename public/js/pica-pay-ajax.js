document.addEventListener('DOMContentLoaded', function() {
	const purchaseButton = document.querySelector('#purchase-button');

	if (!purchaseButton) {
		return;
	}

	document.querySelector('#purchase-button').addEventListener('click', function() {
		console.log('Purchasing article...');

		// Get ppPostId from the button's data attribute and add it to the form data
		const ppPostId = this.getAttribute('data-pp-post-id');
		const nonce = this.getAttribute('data-pp-nonce');

		// Ensure the action parameter is correctly specified
		const formData = new URLSearchParams();
		formData.append('action', 'create_transaction');
		formData.append('pp_post_id', ppPostId);
		formData.append('pp_data_nonce', nonce);

		fetch(picaPayParams.ajaxurl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
			},
			body: formData
		})
			.then(response => {
				if (!response.ok) {
					throw new Error('Network response was not ok');
				}
				console.log('Transaction created:', response);
				return response.json();
			})
			.then(response => {
				if (response.success) {
					console.log('Transaction created:', response.data);
					// Open intent URL in new small window
					window.open(response.data.intentUrl, 'intentWindow', 'width=800,height=800');

					handleArticleFetch(response.data.transactionId, nonce);
				} else {
					console.error('Error creating transaction:', response.data);
				}
			})
			.catch(error => console.error('Error:', error));
	});

	function handleArticleFetch(transactionId, nonce, attempt = 0) {
		if (attempt > 60) {
			alert('Failed to purchase article');
			return;
		}

		const formData = new URLSearchParams();
		formData.append('action', 'poll_transaction_status');
		formData.append('transactionId', transactionId);
		formData.append('pp_data_nonce', nonce);

		fetch(picaPayParams.ajaxurl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
			},
			body: formData
		})
			.then(response => {
				if (!response.ok) {
					throw new Error('Network response was not ok');
				}
				console.log('Polled:', response);
				console.table(response);
				return response.json();
			})
			.then(response => {
				console.log('Success:', response);
				if (response.success && (response.data.status === 'deducted' || response.data.status === 'completed')) {
					alert('Article purchased!  Click OK to refresh page.');
					//refresh page
					location.reload();
				} else {
					setTimeout(function() {
						handleArticleFetch(transactionId, nonce, ++attempt);
					}, 1000);
				}
			})
			.catch(error => console.error('Error:', error));
	}
});

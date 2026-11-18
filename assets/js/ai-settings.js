(function () {
	const data = window.fusAiSettings;
	const providerSelect = document.getElementById('fus-ai-provider');
	if (!data || !providerSelect) {
		return;
	}

	const toggleProviderRows = () => {
		document.querySelectorAll('.fus-ai-provider-row').forEach((row) => {
			row.style.display = row.dataset.provider === providerSelect.value ? '' : 'none';
		});
	};
	providerSelect.addEventListener('change', toggleProviderRows);
	toggleProviderRows();

	const button = document.getElementById('fus-ai-test-connection');
	const result = document.getElementById('fus-ai-test-result');

	const fieldValue = (field) => {
		const input = document.querySelector(`[name="${data.option_name}[${field}]"]`);
		return input ? input.value : '';
	};

	const showResult = (message, isSuccess) => {
		result.textContent = message;
		result.classList.toggle('is-success', isSuccess);
		result.classList.toggle('is-error', !isSuccess);
	};

	button.addEventListener('click', async () => {
		const provider = providerSelect.value;
		const formData = new FormData();
		formData.append('provider', provider);
		formData.append('api_key', fieldValue(`${provider}_key`));
		formData.append('model', fieldValue(`${provider}_model`));
		formData.append('custom_url', fieldValue('custom_url'));

		button.disabled = true;
		showResult(data.i18n.testing, true);

		try {
			const response = await fetch(data.rest_url, {
				method: 'POST',
				body: formData,
				headers: { 'X-WP-Nonce': data.nonce }
			});
			const json = await response.json();
			showResult(json.success ? json.data.message : (json.data?.message || json.message || data.i18n.error), !!json.success);
		} catch (error) {
			showResult(data.i18n.error, false);
		} finally {
			button.disabled = false;
		}
	});
})();

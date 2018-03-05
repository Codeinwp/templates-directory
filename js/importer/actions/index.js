
/**
 * A action used to setup the IS_FETCHING flag
 * @param bool
 * @returns {{type: string, isFetching: *}}
 */
export function isFetching(bool) {
	return {
		type: 'IS_FETCHING',
		isFetching: bool
	};
}

export function fetchHasError(bool) {
	return {
		type: 'FETCH_HAS_ERROR',
		hasError: bool
	};
}

export function isSuccessfulFetch(results) {
	return {
		type: 'FETCH_DATA_SUCCESS',
		results: results
	};
}

export function fetchRemoteData( demo ) {
	return (dispatch) => {

		dispatch(isFetching(true));

		fetch( wpApiSettings.root + 'templates-directory/v1/get_demodata?demo=' + demo + '&nonce=' + importer_endpoint.nonce, {
			headers: {
				'X-WP-Nonce': wpApiSettings.nonce,
			},
			credentials: 'same-origin'
		} )
		.then((response) => {
			if (!response.ok) {throw Error(response.statusText);}
			dispatch(isFetching(false));
			return response;
		})
		.then((response) => response.json())
		.then((results) => dispatch(isSuccessfulFetch(results)))
		.catch(() => dispatch(fetchHasError(true)));
	}
}

export function startDemoDataImport( demo, importType, success ) {
	return async (dispatch) => {
		return await fetch( wpApiSettings.root + 'templates-directory/v1/import_chunk/', {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'X-WP-Nonce': wpApiSettings.nonce,
				'Content-Type': 'application/json'
			},
			body: JSON.stringify({
				demo: demo,
				importType: importType,
				nonce: importer_endpoint.nonce
			})
		} )
		.then((response) => {
			if (!response.ok) {throw Error(response.statusText);}
			return response;
		})
		.then((response) => response.json())
		.then((response) => success( importType, response))
		.catch(() => dispatch(fetchHasError(true)));
	}
}

export function importPlugin( demo, plugin, success ) {
	return async (dispatch) => {

		return await fetch( wpApiSettings.root + 'templates-directory/v1/import_plugin', {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'X-WP-Nonce': wpApiSettings.nonce,
				'Content-Type': 'application/json'
			},
			body: JSON.stringify( {
				plugin: plugin,
				demo: demo,
				nonce: importer_endpoint.nonce
			} )
		} )
		.then((response) => {
			if (!response.ok) {throw Error(response.statusText);}
			return response;
		})
		.then((response) => response.json())
		.catch((err) => {
			console.log( err );
			dispatch(fetchHasError(true));
		});
	}
}

export function activatePlugins( demo ){
	return async (dispatch) => {
		const response = await fetch( wpApiSettings.root + 'templates-directory/v1/activate_plugins', {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'X-WP-Nonce': wpApiSettings.nonce,
				'Content-Type': 'application/json'
			},
			body: JSON.stringify( {
				demo: demo,
				nonce: importer_endpoint.nonce
			} )
		} )
			.then((response) => {
				if (!response.ok) {throw Error(response.statusText);}
				return response;
			})
			.then((response) => response.json())
			.catch(() => dispatch(fetchHasError(true)));

		return await response;
	}
}

export function importMedia( demo, image, success, last = false ) {
	return (dispatch) => {
		return fetch( wpApiSettings.root + 'templates-directory/v1/import_media', {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'X-WP-Nonce': wpApiSettings.nonce,
				'Content-Type': 'application/json'
			},
			body: JSON.stringify( {
				image: image,
				demo: demo,
				last: last,
				nonce: importer_endpoint.nonce
			} )
		} )
			.then((response) => {
				if (!response.ok) {throw Error(response.statusText);}
				return response;
			})
			.then((response) => response.json())
			.then((response) => success( 'image', response))
			.catch(() => dispatch(fetchHasError(true)));
	}
}

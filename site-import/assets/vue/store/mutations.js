/* jshint esversion: 6 */
const setAjaxState = ( state, data ) => {
	state.ajaxLoader = data;
};
const setImportingState = ( state, data ) => {
	state.importing = data;
};
const saveSitesData = ( state, data ) => {
	state.sitesData = data;
};
const showPreview = ( state, data ) => {
	state.previewOpen = data;
};
const showImportModal = ( state, data ) => {
	state.importModalState = data;
};
const populatePreview = ( state, data ) => {
	state.previewData = data;
};

export default {
	setAjaxState,
	saveSitesData,
	showPreview,
	showImportModal,
	populatePreview,
	setImportingState
};
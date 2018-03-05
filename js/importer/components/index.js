import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import { createStore, applyMiddleware } from 'redux';
import thunk from 'redux-thunk';

import reducers from '../reducers/index';

const createStoreWithMiddleware = applyMiddleware(thunk)(createStore);
const store = createStoreWithMiddleware(reducers);

import App from './app'

$(document).on('initDemoImporter', function (event,data) {

	ReactDOM.render(
		<Provider store={store}>
			<App {...data}/>
		</Provider>,
		document.getElementById('demoDataImporter')
	);
});

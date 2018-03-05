import { combineReducers } from 'redux';

export function dataHasErrored(state = false, action) {
	switch (action.type) {
		case 'FETCH_HAS_ERROR':
			return action.hasError;

		default:
			return state;
	}
}

export function dataIsLoading(state = false, action) {
	switch (action.type) {
		case 'IS_FETCHING':
			return action.isFetching;
		default:
			return state;
	}
} 

export function getData(state = [], action) {
	switch (action.type) {
		case 'FETCH_DATA_SUCCESS':
			return action.results;

		default:
			return state;
	}
}

const rootReducer = combineReducers({
	isFetching: dataIsLoading,
	data: getData,
	hasError: dataHasErrored
});

export default rootReducer;
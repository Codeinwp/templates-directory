import React from 'react'
import PropTypes from 'prop-types';

import Demo from '../containers/demo';

class App extends React.Component {

	constructor(props) {
		super(props);

	}

	render(){
		return (<div>
			<h2>{this.props.name}</h2>
			<Demo name={this.props.name} />
		</div>)
	}
}

App.propTypes = {
	name: PropTypes.string.isRequired,
	url: PropTypes.string.isRequired
};

export default App
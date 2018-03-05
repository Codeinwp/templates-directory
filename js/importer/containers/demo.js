import React from 'react';
import {Button, Spin, Alert, Modal} from 'antd';
import {bindActionCreators} from "redux";
import {connect} from "react-redux";

import {
	fetchRemoteData,
	startDemoDataImport,
	importPlugin,
	activatePlugins,
	importMedia
} from '../actions';

let wait = async (time) => await new Promise(
	res => setTimeout(() => res(), time)
);

class Demo extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			visible: false,
			status: null,
			imported: []
		};

		this.triggerImport = this.triggerImport.bind(this);
		this.modalConfirm = this.modalConfirm.bind(this);
		this.modalCancel = this.modalCancel.bind(this);
		this.updateImported = this.updateImported.bind(this);
		this.importPlugins = this.importPlugins.bind(this);
		this.importMedia = this.importMedia.bind(this);
	}

	componentWillMount() {
		this.props.fetchRemoteData(this.props.name);
	}

	componentDidMount() {
		window.addEventListener("importDemoData", this.triggerImport);
	}

	componentWillUnmount() {
		window.removeEventListener("importDemoData", this.triggerImport);
	}

	render() {
		const {fetched} = this.props;
		const {blogname, description, screenshot} = fetched;

		if (typeof this.props.fetched === "undefined" || this.props.isFetching) {
			return <Spin/>
		}

		let statusMsg = null;

		if (blogname) {
			statusMsg = <Alert
				message={blogname}
				label={blogname}
				type="success"
				showIcon/>
		} else {
			statusMsg = <Alert
				message="Demo is not available"
				type="error"
				showIcon/>
		}

		return (<div>
			<div>{statusMsg}</div>

			<div className="preview">
				<h3>{blogname}</h3>
				<p>{description}</p>
				<span style={{textAlign: 'center'}}>
				{screenshot ? <img src={screenshot} alt="Preview" width="250"/> : null}
				</span>

				<div>
					{this.state.status
						? <div>
							<Spin/> {this.state.status}
						</div>
						: null}
				</div>
			</div>

			<Modal
				title="Import"
				visible={this.state.visible}
				onOk={this.modalConfirm}
				onCancel={this.modalCancel}
				cancelText="Cancel"
				okText="Import"
			>
				<strong>Are you sure?</strong>
				<p>Importing a demo means things like:</p>
				<ol>
					<li>Posts</li>
					<li>Categories</li>
					<li>Pages</li>
					<li>Menus</li>
					<li>Widgets</li>
					<li>Plugins</li>
				</ol>

				<p>They will be imported in your website to build a perfect copy of the selected demo.</p>

				<p><strong>Note:</strong> Some options like static pages may be changed</p>

			</Modal>
		</div>)
	}

	triggerImport() {
		this.setState({visible: true});
	}

	modalConfirm() {
		this.setState({visible: false});

		this.setState({
			status: 'Start import'
		});

		wait(300)
			.then(() => {
				return Promise.resolve(this.props.startDemoDataImport(this.props.name, 'before_settings', this.updateImported));
			})
			.then((p) => {
				this.setState({
					status: 'Installing plugins'
				});
				return this.importPlugins();
			})
			.then((p) => {
				this.setState({
					status: 'Activate plugins'
				});
				return Promise.resolve(this.props.activatePlugins(this.props.name));
			})
			.then(async (p) => {
				this.setState({
					status: 'Import media'
				});
				return await this.importMedia();
			})
			.then( async (p) => {
				this.setState({
					status: 'Save media'
				});

				console.log(  this.state.imported.images );

				// send a list of imported media ids to the server
				return await this.props.importMedia( this.props.name, 1, this.updateImported, this.state.imported.images );
			})
			.then((p) => {
				this.setState({
					status: 'Importing taxonomies'
				});

				return Promise.resolve(this.props.startDemoDataImport(this.props.name, 'taxonomies', this.updateImported));
			})
			.then((p) => {
				this.setState({
					status: 'Importing posts'
				});

				return Promise.resolve(this.props.startDemoDataImport(this.props.name, 'post_types', this.updateImported));
			})
			.then((p) => {
				this.setState({
					status: 'Importing widgets'
				});

				return Promise.resolve(this.props.startDemoDataImport(this.props.name, 'widgets', this.updateImported));
			})
			.then((p) => {
				this.props.startDemoDataImport(this.props.name, 'after_settings', this.updateImported);

				this.setState({
					status: 'Done!'
				});

				console.log(this.state);
			})
			.catch((err) => {
				throw new Error('Higher-level error. ' + err.message);
			});
	}

	modalCancel() {
		this.setState({visible: false});
	}

	async importPlugins() {
		const {active_plugins} = this.props.fetched;

		if (typeof active_plugins === "undefined") {
			return;
		}

		return await Promise.all(active_plugins.map(async (plugins) => {
			return await this.props.importPlugin(this.props.name, plugins, this.updateImported);
		}));
	}

	async importMedia() {
		const {media} = this.props.fetched;

		return await Promise.all(media.images.map(async (image) => {
			return this.props.importMedia(this.props.name, image, this.updateImported);
		}));
	}

	importData() {
		const data = this.props.fetched;

		Object.keys(data).filter((key) => {
			const allowedKeys = ['post_types', 'taxonomies', 'widgets', 'before_settings', 'after_settings'];

			if (allowedKeys.indexOf(key) === -1) {
				return null;
			}

			this.props.startDemoDataImport(this.props.name, key, this.updateImported);
		});

		return {}
	}

	updateImported(tag, result) {
		this.setState((prevState, props) => {
			let {imported} = prevState;
			// console.log(tag);
			// console.log(result);
			imported[tag] = result;

			return {
				imported: imported
			};
		});
	}
}

const mapStateToProps = (state) => {
	// Whatever is returned will show up as props
	// inside of Proposals

	return {
		isFetching: state.isFetching,
		fetched: state.data
	};
};

// Anything returned from this function will end up as props
// on the Proposals container
function mapDispatchToProps(dispatch) {
	return bindActionCreators({
		fetchRemoteData: fetchRemoteData,
		startDemoDataImport: startDemoDataImport,
		importPlugin: importPlugin,
		activatePlugins: activatePlugins,
		importMedia: importMedia
	}, dispatch);
}

// Promote Proposals from a component to a container - it needs to know
// about this new dispatch method, selectBook. Make it available
// as a prop.
export default connect(mapStateToProps, mapDispatchToProps)(Demo);
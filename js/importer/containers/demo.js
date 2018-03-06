import React from 'react';
import {bindActionCreators} from "redux";
import {connect} from "react-redux";
import Modal from '../components/Modal';

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
			return <span className="spinner"></span>
		}

		return (
			<div className="preview">
				<h3 className="theme-name">{blogname}</h3>
				{screenshot ? <img className="theme-screenshot" src={screenshot} alt={blogname} width="250"/> : null}
				<p className="theme-details">{description}</p>
				<div>
					{this.state.status
						? <div className="demo-import-status">
							<strong>{this.state.status}</strong>
							{this.state.status !== 'Done!' &&  <span className="spinner"></span>}
						</div>
						: null}
				</div>

			{
				this.state.visible && <Modal
				blogname={blogname}
				onOk={this.modalConfirm}
				onCancel={this.modalCancel}
			/>
				}
            </div>
			)
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
				location.reload();
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
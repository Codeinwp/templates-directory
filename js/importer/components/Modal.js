import React from 'react';

class Modal extends React.Component {
    constructor(props) {
        super(props);

        this.setModalRef = this.setModalRef.bind(this);
        this.handleClickOutside = this.handleClickOutside.bind(this);
    }

    componentDidMount() {
        document.addEventListener('mousedown', this.handleClickOutside);
    }

    componentWillUnmount() {
        document.removeEventListener('mousedown', this.handleClickOutside);
    }

    setModalRef(node) {
        this.wrapperRef = node;
    }

    /**
     * Alert if clicked on outside of element
     */
    handleClickOutside(event) {
        if (this.wrapperRef && !this.wrapperRef.contains(event.target)) {
            this.props.onCancel();
        }
    }

    render() {
        return (
            <div className="modal-wrap">
                <div className="modal" ref={this.setModalRef}>
                    <div className="modal-header">
                        <h3 className="title">Import site: {this.props.blogname}</h3>
                        <span onClick={this.props.onCancel} className="close dashicons dashicons-no-alt"></span>
                    </div>
                    <div className="modal-body">
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
                    </div>
                    <div className="modal-footer">
                        <button onClick={this.props.onCancel} className="button">Cancel</button>
                        <button onClick={this.props.onOk} className="button button-primary">Import</button>
                    </div>
                </div>
            </div>
        )
    }
}

export default Modal;
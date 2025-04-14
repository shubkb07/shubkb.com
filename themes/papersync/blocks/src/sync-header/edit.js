import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { useEffect, useState } from '@wordpress/element';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	Button,
} from '@wordpress/components';

import MultiSelectSort from '../@anfco/shared/components/SelectDnd';
import ServerSideRender from '@wordpress/server-side-render';
import './editor.scss';
import metadata from './block.json';

export default function Edit( { attributes, setAttributes } ) {
	const {
		posts,
		searchTitle,
		items,
		postType,
		order,
	} = attributes;

	const [ awardSelectOptions, setAwardSelectOptions ] = useState( [] );

	// Get awards posts.
	const awards = useSelect(
		( select ) => {
			return select( coreStore ).getEntityRecords( 'postType', 'awards', { per_page: 10, search: searchTitle ? searchTitle : '' } );
		},
		[ searchTitle ]
	);

	// Set dropdown values.
	useEffect( () => {
		let awardOptions = [];

		if ( null !== awards ) {
			awardOptions = ! Array.isArray( awards )
				? awards
				: awards
					.map( ( post ) => ( {
						label: post.title.raw,
						value: post.id,
					} ) );
		}

		setAwardSelectOptions( awardOptions );
	}, [ awards ] );

	const onChange = ( selectedOptions ) => {
		setAttributes( { posts: selectedOptions } );
		setAttributes( { searchTitle: '' } );
	};

	const onInputChange = ( value ) => {
		setAttributes( { searchTitle: value } );
	};

	const arrayMove = ( array, from, to ) => {
		const slicedArray = array.slice();
		slicedArray.splice(
			to < 0 ? array.length + to : to,
			0,
			slicedArray.splice( from, 1 )[ 0 ]
		);
		return slicedArray;
	};

	const onSortEnd = ( { oldIndex, newIndex } ) => {
		const newValue = arrayMove( posts, oldIndex, newIndex );
		setAttributes( { posts: newValue } );
	};

	const clearAll = () => {
		setAttributes( { posts: [] } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Slider Settings', 'anfco' ) }>
					<MultiSelectSort
						options={ awardSelectOptions }
						classNamePrefix={ 'award' }
						value={ posts }
						onChange={ onChange }
						onInputChange={ onInputChange }
						onSortEnd={ onSortEnd }
						label={ __( 'Select Award', 'anfco' ) }
						placeholder={ __( 'Select Award', 'anfco' ) }
						isClearable={ false }
					/>
					{ 0 < posts.length &&
						<Button
							variant="secondary"
							onClick={ clearAll }
							isSmall={ true }
							className={ 'clear-selected-list' }
						>
							{ __( 'Clear All', 'anfco' ) }
						</Button>
					}
					{ 0 === posts.length &&
						<>
							<RangeControl
								label={ __( 'Slider Item(s)', 'anfco' ) }
								value={ items }
								onChange={ ( value ) => setAttributes( { items: value } ) }
								min={ 1 }
								max={ 20 }
							/>

							<SelectControl
								label={ __( 'Post Order', 'anfco' ) }
								value={ order }
								options={ [
									{ label: __( 'Ascending' ), value: 'asc' },
									{ label: __( 'Descending' ), value: 'desc' },
								] }
								onChange={ ( value ) => setAttributes( { order: value } ) }
							/>
						</>
					}
				</PanelBody>
			</InspectorControls>

			<div { ...useBlockProps() }>
				<ServerSideRender
					block={ metadata.name }
					attributes={ {
						posts,
						items,
						postType,
						order,
					} }
					onChange={ ( newAttributes ) => {
						setAttributes( newAttributes );
					} }
				/>
			</div>
		</>
	);
}

// Award slider.
function anfcoAwardSlider() {
	const deviceWidth = window.innerWidth;

	const awardSliders = document.querySelectorAll( '.anfco-awards-section' );

	awardSliders.forEach( ( slider, index ) => {
		const slideCount = slider.querySelector( 'ul' ).childElementCount;

		if ( slideCount > 4 || ( slideCount === 4 && deviceWidth <= 820 ) || ( slideCount === 3 && deviceWidth <= 768 ) || ( slideCount === 2 && deviceWidth <= 480 ) ) {
			slider.setAttribute( 'id', 'awards-slider-' + index );
			slider.setAttribute( 'class', 'splide anfco-award-slider' );
			slider.setAttribute( 'aria-roledescription', 'carousel' );
			slider.setAttribute( 'aria-label', 'award Slider' );

			const awardsList = slider.querySelector( 'ul' );
			if ( null !== awardsList && awardsList ) {
				awardsList.setAttribute( 'class', 'splide__list' );
				const awardsContainer = document.createElement( 'div' );
				awardsContainer.setAttribute( 'class', 'splide__track' );
				awardsContainer.appendChild( awardsList );
				slider.appendChild( awardsContainer );

				const sliderListItems = awardsList.querySelectorAll( 'li' );
				if ( sliderListItems && sliderListItems.length > 0 ) {
					sliderListItems.forEach( ( listItem ) => {
						listItem.setAttribute( 'class', 'splide__slide' );
					} );
				}
			}

			if ( 3 >= slideCount ) {
				slider.classList.add( 'is-center-slide' );
			}

			if ( 4 >= slideCount ) {
				slider.classList.add( 'is-arrow-hidden' );
			}

			const splide = new Splide( slider, { // eslint-disable-line no-undef
				updateOnMove: true,
				type: 'slide',
				perPage: 4,
				perMove: 1,
				cloneStatus: false,
				gap: 20,
				pagination: false,
				breakpoints: {
					820: {
						perPage: 3,
					},
					768: {
						perPage: 2,
					},
					480: {
						perPage: 1,
					},
				},
				live: false,
			} );

			splide.on( 'mounted', function() {
				splide.root.removeAttribute( 'role' );
			} );

			splide.mount();
		}
	} );
}

window.addEventListener( 'DOMContentLoaded', anfcoAwardSlider );

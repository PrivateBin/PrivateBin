import $ from 'jquery'

/**
 * Helper functions for user interface
 *
 * everything directly UI-related, which fits nowhere else
 *
 * @name   UiHelper
 * @class
 */

/**
 * handle history (pop) state changes
 *
 * currently this does only handle redirects to the home page.
 *
 * @name   UiHelper.historyChange
 * @private
 * @function
 * @param  {Event} event
 */
function historyChange(event)
{
    var currentLocation = Helper.baseUri();
    if (event.originalEvent.state === null && // no state object passed
        event.target.location.href === currentLocation && // target location is home page
        window.location.href === currentLocation // and we are not already on the home page
    ) {
        // redirect to home page
        window.location.href = currentLocation;
    }
}

/**
 * reload the page
 *
 * This takes the user to the PrivateBin homepage.
 *
 * @name   UiHelper.reloadHome
 * @function
 */
export function reloadHome()
{
    window.location.href = Helper.baseUri();
}

/**
 * checks whether the element is currently visible in the viewport (so
 * the user can actually see it)
 *
 * @see    {@link https://stackoverflow.com/a/40658647}
 * @name   UiHelper.isVisible
 * @function
 * @param  {jQuery} $element The link hash to move to.
 */
export function isVisible($element)
{
    var elementTop = $element.offset().top;
    var viewportTop = $(window).scrollTop();
    var viewportBottom = viewportTop + $(window).height();

    return elementTop > viewportTop && elementTop < viewportBottom;
}

/**
 * scrolls to a specific element
 *
 * @see    {@link https://stackoverflow.com/questions/4198041/jquery-smooth-scroll-to-an-anchor#answer-12714767}
 * @name   UiHelper.scrollTo
 * @function
 * @param  {jQuery}           $element        The link hash to move to.
 * @param  {(number|string)}  animationDuration passed to jQuery .animate, when set to 0 the animation is skipped
 * @param  {string}           animationEffect   passed to jQuery .animate
 * @param  {function}         finishedCallback  function to call after animation finished
 */
export function scrollTo($element, animationDuration, animationEffect, finishedCallback)
{
    var $body = $('html, body'),
        margin = 50,
        callbackCalled = false;

    //calculate destination place
    var dest = 0;
    // if it would scroll out of the screen at the bottom only scroll it as
    // far as the screen can go
    if ($element.offset().top > $(document).height() - $(window).height()) {
        dest = $(document).height() - $(window).height();
    } else {
        dest = $element.offset().top - margin;
    }
    // skip animation if duration is set to 0
    if (animationDuration === 0) {
        window.scrollTo(0, dest);
    } else {
        // stop previous animation
        $body.stop();
        // scroll to destination
        $body.animate({
            scrollTop: dest
        }, animationDuration, animationEffect);
    }

    // as we have finished we can enable scrolling again
    $body.queue(function (next) {
        if (!callbackCalled) {
            // call user function if needed
            if (typeof finishedCallback !== 'undefined') {
                finishedCallback();
            }

            // prevent calling this function twice
            callbackCalled = true;
        }
        next();
    });
}

/**
 * trigger a history (pop) state change
 *
 * used to test the UiHelper.historyChange private function
 *
 * @name   UiHelper.mockHistoryChange
 * @function
 * @param  {string} state   (optional) state to mock
 */
export function mockHistoryChange(state)
{
    if (typeof state === 'undefined') {
        state = null;
    }
    historyChange($.Event('popstate', {originalEvent: new PopStateEvent('popstate', {state: state}), target: window}));
}

/**
 * initialize
 *
 * @name   UiHelper.init
 * @function
 */
export function init()
{
    // update link to home page
    $('.reloadlink').prop('href', Helper.baseUri());

    $(window).on('popstate', historyChange);
}

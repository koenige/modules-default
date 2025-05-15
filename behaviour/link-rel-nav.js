/*
 * default module
 * navigation with <link rel=""> elements for arrow keys and swipe
 * 
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 * 
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010, 2013, 2018-2019, 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function defNavigate (myevent) {
	if (!myevent)
		var myevent = window.event;
	if (myevent.which) {
		var Code = myevent.which;
	} else if (myevent.keyCode) {
		var Code = myevent.keyCode;
	}
	if (myevent.altKey) return;
	// only allow <- -> navigation if body has focus
	if (document.activeElement.nodeName !== "BODY") return;
	if ((Code == 37 || Code == 38 || Code == 39)) {
		defNavigatePrevNext(Code);
  	}
}

function defNavigatePrevNext(Code) {
	var Links = document.getElementsByTagName("link");
	for (var intLink = 0; intLink < Links.length; intLink++) {
		var el = Links[intLink];
		if ("prev" == el.rel && Code == 37) {
			window.location.href = el.href;
		}
		if ("next" == el.rel && Code == 39) {
			window.location.href = el.href;
		}
		if ("up" == el.rel && Code == 38) {
			window.location.href = el.href;
		}
	}
}

document.onkeyup = defNavigate;

/* Swipe */

document.addEventListener('touchstart', defHandleTouchStart, false);        
document.addEventListener('touchmove', defHandleTouchMove, false);

var xDown = null;                                                        
var yDown = null;                                                        

function defHandleTouchStart(evt) {                                         
    // Check if touch originated within a table
    if (evt.target.closest('table')) {
        return;
    }
    xDown = evt.touches[0].clientX;                                      
    yDown = evt.touches[0].clientY;                                      
};                                                


function defHandleTouchMove(evt) {
    // Check if touch originated within a table
    if (evt.target.closest('table')) {
        return;
    }
    if ( ! xDown || ! yDown ) {
        return;
    }

    var xUp = evt.touches[0].clientX;                                    
    var yUp = evt.touches[0].clientY;

    var xDiff = xDown - xUp;
    var yDiff = yDown - yUp;

    if ( Math.abs( xDiff ) > Math.abs( yDiff ) ) {/*most significant*/
        if ( xDiff > 0 ) {
            /* left swipe */ 
	  		defNavigatePrevNext(39);
        } else {
            /* right swipe */
	  		defNavigatePrevNext(37);
        }                       
    } else {
        if ( yDiff > 0 ) {
            /* up swipe */ 
	  		defNavigatePrevNext(38);
        } else { 
            /* down swipe */
	  		defNavigatePrevNext(40);
        }                                                                 
    }
    /* reset values */
    xDown = null;
    yDown = null;                                             
};

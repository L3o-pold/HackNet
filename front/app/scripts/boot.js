var dialogue	=	document.getElementsByClassName("dialogue")[0];

/** Define a simple wrapper for the dialogue's classList to allow us to lazily/easily toggle its open state. */
1 || (function(o){
    var open = false;
    Object.defineProperty(o, "open", {
        get:	function(){ return o.classList.contains("open"); },
        set:	function(i){ o.classList.toggle("open", !!i); }
    });
}(dialogue));

function snakeText(from, to, refreshRate, charAmount, autoScroll){
    var fromText, toText, l, i,
        refreshRate		=	refreshRate	|| 20,
        charAmount		=	charAmount	|| 1;

    /** Check if a textNode was passed directly. */
    if(3 === from.nodeType) fromText = from;

    /** If not, scan the element's direct descendants for one. */
    else for(i = l = from.childNodes.length-1; i >= 0; --i)
        if(3 === from.childNodes[i].nodeType){
            fromText = from.childNodes[i];
            break;
        }

    /** Uhm. We kinda need a source, here... */
    if(!fromText) throw new ArgumentError("Source object is neither a text node or element containing any text nodes.");


    /** Repeat the above procedure. */
    if(3 === to.nodeType) toText = to;
    else for(i = l = to.childNodes.length-1; i >= 0; --i)
        if(3 === to.childNodes[i].nodeType){
            toText	=	to.childNodes[i];
            break;
        }

    /** Create a new text node if an existing one wasn't found. */
    toText	=	toText || to.appendChild(document.createTextNode(""));


    var interval = setInterval(function(){
        var from	=	fromText.data;
        if(!from.length) return clearInterval(interval);
        toText.data		+=	from.substr(0, charAmount);
        fromText.data	=	from.substr(charAmount);

        if(autoScroll)
            window.scrollTo(0, document.body.scrollHeight);
    }, refreshRate);
    return interval;
}

snakeText(document.createTextNode("ISOLINUX 3.31 2015 Copyright (C)\nArch Linux"), document.getElementsByClassName('loading')[0], 10, 4, true);
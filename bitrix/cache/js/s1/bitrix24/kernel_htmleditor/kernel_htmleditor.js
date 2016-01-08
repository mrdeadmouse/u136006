; /* /bitrix/js/fileman/html_editor/range.js?1452277448149045*/
; /* /bitrix/js/fileman/html_editor/html-actions.min.js?145227744854400*/
; /* /bitrix/js/fileman/html_editor/html-views.min.js?145227744832129*/
; /* /bitrix/js/fileman/html_editor/html-parser.min.js?145227744850418*/
; /* /bitrix/js/fileman/html_editor/html-base-controls.min.js?145227744858155*/
; /* /bitrix/js/fileman/html_editor/html-controls.min.js?1452277448115821*/
; /* /bitrix/js/fileman/html_editor/html-components.js?145227744812842*/
; /* /bitrix/js/fileman/html_editor/html-snippets.js?145227744828100*/
; /* /bitrix/js/fileman/html_editor/html-editor.min.js?145227744868327*/

; /* Start:"a:4:{s:4:"full";s:56:"/bitrix/js/fileman/html_editor/range.js?1452277448149045";s:6:"source";s:39:"/bitrix/js/fileman/html_editor/range.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
/**
 * Rangy, a cross-browser JavaScript range and selection library
 * http://code.google.com/p/rangy/
 *
 * Copyright 2013, Tim Down
 * Licensed under the MIT license.
 * Version: 1.3alpha.804
 * Build date: 8 December 2013
 */

(function(global) {
    var amdSupported = (typeof global.define == "function" && global.define.amd);

    var OBJECT = "object", FUNCTION = "function", UNDEFINED = "undefined";

    // Minimal set of properties required for DOM Level 2 Range compliance. Comparison constants such as START_TO_START
    // are omitted because ranges in KHTML do not have them but otherwise work perfectly well. See issue 113.
    var domRangeProperties = ["startContainer", "startOffset", "endContainer", "endOffset", "collapsed",
        "commonAncestorContainer"];

    // Minimal set of methods required for DOM Level 2 Range compliance
    var domRangeMethods = ["setStart", "setStartBefore", "setStartAfter", "setEnd", "setEndBefore",
        "setEndAfter", "collapse", "selectNode", "selectNodeContents", "compareBoundaryPoints", "deleteContents",
        "extractContents", "cloneContents", "insertNode", "surroundContents", "cloneRange", "toString", "detach"];

    var textRangeProperties = ["boundingHeight", "boundingLeft", "boundingTop", "boundingWidth", "htmlText", "text"];

    // Subset of TextRange's full set of methods that we're interested in
    var textRangeMethods = ["collapse", "compareEndPoints", "duplicate", "moveToElementText", "parentElement", "select",
        "setEndPoint", "getBoundingClientRect"];

    /*----------------------------------------------------------------------------------------------------------------*/

    // Trio of functions taken from Peter Michaux's article:
    // http://peter.michaux.ca/articles/feature-detection-state-of-the-art-browser-scripting
    function isHostMethod(o, p) {
        var t = typeof o[p];
        return t == FUNCTION || (!!(t == OBJECT && o[p])) || t == "unknown";
    }

    function isHostObject(o, p) {
        return !!(typeof o[p] == OBJECT && o[p]);
    }

    function isHostProperty(o, p) {
        return typeof o[p] != UNDEFINED;
    }

    // Creates a convenience function to save verbose repeated calls to tests functions
    function createMultiplePropertyTest(testFunc) {
        return function(o, props) {
            var i = props.length;
            while (i--) {
                if (!testFunc(o, props[i])) {
                    return false;
                }
            }
            return true;
        };
    }

    // Next trio of functions are a convenience to save verbose repeated calls to previous two functions
    var areHostMethods = createMultiplePropertyTest(isHostMethod);
    var areHostObjects = createMultiplePropertyTest(isHostObject);
    var areHostProperties = createMultiplePropertyTest(isHostProperty);

    function isTextRange(range) {
        return range && areHostMethods(range, textRangeMethods) && areHostProperties(range, textRangeProperties);
    }

    function getBody(doc) {
        return isHostObject(doc, "body") ? doc.body : doc.getElementsByTagName("body")[0];
    }

    var modules = {};

    var api = {
        version: "1.3alpha.804",
        initialized: false,
        supported: true,

        util: {
            isHostMethod: isHostMethod,
            isHostObject: isHostObject,
            isHostProperty: isHostProperty,
            areHostMethods: areHostMethods,
            areHostObjects: areHostObjects,
            areHostProperties: areHostProperties,
            isTextRange: isTextRange,
            getBody: getBody
        },

        features: {},

        modules: modules,
        config: {
            alertOnFail: true,
            alertOnWarn: false,
            preferTextRange: false
        }
    };

    function consoleLog(msg) {
        if (isHostObject(window, "console") && isHostMethod(window.console, "log")) {
            window.console.log(msg);
        }
    }

    function alertOrLog(msg, shouldAlert) {
        if (shouldAlert) {
            window.alert(msg);
        } else  {
            consoleLog(msg);
        }
    }

    function fail(reason) {
        api.initialized = true;
        api.supported = false;
        alertOrLog("Rangy is not supported on this page in your browser. Reason: " + reason, api.config.alertOnFail);
    }

    api.fail = fail;

    function warn(msg) {
        alertOrLog("Rangy warning: " + msg, api.config.alertOnWarn);
    }

    api.warn = warn;

    // Add utility extend() method
    if ({}.hasOwnProperty) {
        api.util.extend = function(obj, props, deep) {
            var o, p;
            for (var i in props) {
                if (props.hasOwnProperty(i)) {
                    o = obj[i];
                    p = props[i];
                    //if (deep) alert([o !== null, typeof o == "object", p !== null, typeof p == "object"])
                    if (deep && o !== null && typeof o == "object" && p !== null && typeof p == "object") {
                        api.util.extend(o, p, true);
                    }
                    obj[i] = p;
                }
            }
            return obj;
        };
    } else {
        fail("hasOwnProperty not supported");
    }

    // Test whether Array.prototype.slice can be relied on for NodeLists and use an alternative toArray() if not
    (function() {
        var el = document.createElement("div");
        el.appendChild(document.createElement("span"));
        var slice = [].slice;
        var toArray;
        try {
            if (slice.call(el.childNodes, 0)[0].nodeType == 1) {
                toArray = function(arrayLike) {
                    return slice.call(arrayLike, 0);
                };
            }
        } catch (e) {}

        if (!toArray) {
            toArray = function(arrayLike) {
                var arr = [];
                for (var i = 0, len = arrayLike.length; i < len; ++i) {
                    arr[i] = arrayLike[i];
                }
                return arr;
            };
        }

        api.util.toArray = toArray;
    })();


    // Very simple event handler wrapper function that doesn't attempt to solve issues such as "this" handling or
    // normalization of event properties
    var addListener;
    if (isHostMethod(document, "addEventListener")) {
        addListener = function(obj, eventType, listener) {
            obj.addEventListener(eventType, listener, false);
        };
    } else if (isHostMethod(document, "attachEvent")) {
        addListener = function(obj, eventType, listener) {
            obj.attachEvent("on" + eventType, listener);
        };
    } else {
        fail("Document does not have required addEventListener or attachEvent method");
    }

    api.util.addListener = addListener;

    var initListeners = [];

    function getErrorDesc(ex) {
        return ex.message || ex.description || String(ex);
    }

    // Initialization
    function init() {
        if (api.initialized) {
            return;
        }
        var testRange;
        var implementsDomRange = false, implementsTextRange = false;

        // First, perform basic feature tests

        if (isHostMethod(document, "createRange")) {
            testRange = document.createRange();
            if (areHostMethods(testRange, domRangeMethods) && areHostProperties(testRange, domRangeProperties)) {
                implementsDomRange = true;
            }
            testRange.detach();
        }

        var body = getBody(document);
        if (!body || body.nodeName.toLowerCase() != "body") {
            fail("No body element found");
            return;
        }

        if (body && isHostMethod(body, "createTextRange")) {
            testRange = body.createTextRange();
            if (isTextRange(testRange)) {
                implementsTextRange = true;
            }
        }

        if (!implementsDomRange && !implementsTextRange) {
            fail("Neither Range nor TextRange are available");
            return;
        }

        api.initialized = true;
        api.features = {
            implementsDomRange: implementsDomRange,
            implementsTextRange: implementsTextRange
        };

        // Initialize modules
        var module, errorMessage;
        for (var moduleName in modules) {
            if ( (module = modules[moduleName]) instanceof Module ) {
                module.init(module, api);
            }
        }

        // Call init listeners
        for (var i = 0, len = initListeners.length; i < len; ++i) {
            try {
                initListeners[i](api);
            } catch (ex) {
                errorMessage = "Rangy init listener threw an exception. Continuing. Detail: " + getErrorDesc(ex);
                consoleLog(errorMessage);
            }
        }
    }

    // Allow external scripts to initialize this library in case it's loaded after the document has loaded
    api.init = init;

    // Execute listener immediately if already initialized
    api.addInitListener = function(listener) {
        if (api.initialized) {
            listener(api);
        } else {
            initListeners.push(listener);
        }
    };

    var createMissingNativeApiListeners = [];

    api.addCreateMissingNativeApiListener = function(listener) {
        createMissingNativeApiListeners.push(listener);
    };

    function createMissingNativeApi(win) {
        win = win || window;
        init();

        // Notify listeners
        for (var i = 0, len = createMissingNativeApiListeners.length; i < len; ++i) {
            createMissingNativeApiListeners[i](win);
        }
    }

    api.createMissingNativeApi = createMissingNativeApi;

    function Module(name, dependencies, initializer) {
        this.name = name;
        this.dependencies = dependencies;
        this.initialized = false;
        this.supported = false;
        this.initializer = initializer;
    }

    Module.prototype = {
        init: function(api) {
            var requiredModuleNames = this.dependencies || [];
            for (var i = 0, len = requiredModuleNames.length, requiredModule, moduleName; i < len; ++i) {
                moduleName = requiredModuleNames[i];

                requiredModule = modules[moduleName];
                if (!requiredModule || !(requiredModule instanceof Module)) {
                    throw new Error("required module '" + moduleName + "' not found");
                }

                requiredModule.init();

                if (!requiredModule.supported) {
                    throw new Error("required module '" + moduleName + "' not supported");
                }
            }

            // Now run initializer
            this.initializer(this)
        },

        fail: function(reason) {
            this.initialized = true;
            this.supported = false;
            throw new Error("Module '" + this.name + "' failed to load: " + reason);
        },

        warn: function(msg) {
            api.warn("Module " + this.name + ": " + msg);
        },

        deprecationNotice: function(deprecated, replacement) {
            api.warn("DEPRECATED: " + deprecated + " in module " + this.name + "is deprecated. Please use "
                + replacement + " instead");
        },

        createError: function(msg) {
            return new Error("Error in Rangy " + this.name + " module: " + msg);
        }
    };

    function createModule(isCore, name, dependencies, initFunc) {
        var newModule = new Module(name, dependencies, function(module) {
            if (!module.initialized) {
                module.initialized = true;
                try {
                    initFunc(api, module);
                    module.supported = true;
                } catch (ex) {
                    var errorMessage = "Module '" + name + "' failed to load: " + getErrorDesc(ex);
                    consoleLog(errorMessage);
                }
            }
        });
        modules[name] = newModule;

/*
        // Add module AMD support
        if (!isCore && amdSupported) {
            global.define(["rangy-core"], function(rangy) {

            });
        }
*/
    }

    api.createModule = function(name) {
        // Allow 2 or 3 arguments (second argument is an optional array of dependencies)
        var initFunc, dependencies;
        if (arguments.length == 2) {
            initFunc = arguments[1];
            dependencies = [];
        } else {
            initFunc = arguments[2];
            dependencies = arguments[1];
        }
        createModule(false, name, dependencies, initFunc);
    };

    api.createCoreModule = function(name, dependencies, initFunc) {
        createModule(true, name, dependencies, initFunc);
    };

    /*----------------------------------------------------------------------------------------------------------------*/

    // Ensure rangy.rangePrototype and rangy.selectionPrototype are available immediately

    function RangePrototype() {}
    api.RangePrototype = RangePrototype;
    api.rangePrototype = new RangePrototype();

    function SelectionPrototype() {}
    api.selectionPrototype = new SelectionPrototype();

    /*----------------------------------------------------------------------------------------------------------------*/

    // Wait for document to load before running tests

    var docReady = false;

    var loadHandler = function(e) {
        if (!docReady) {
            docReady = true;
            if (!api.initialized) {
                init();
            }
        }
    };

    // Test whether we have window and document objects that we will need
    if (typeof window == UNDEFINED) {
        fail("No window found");
        return;
    }
    if (typeof document == UNDEFINED) {
        fail("No document found");
        return;
    }

    if (isHostMethod(document, "addEventListener")) {
        document.addEventListener("DOMContentLoaded", loadHandler, false);
    }

    // Add a fallback in case the DOMContentLoaded event isn't supported
    addListener(window, "load", loadHandler);

    /*----------------------------------------------------------------------------------------------------------------*/

    // AMD, for those who like this kind of thing

    if (amdSupported) {
        // AMD. Register as an anonymous module.
        global.define(function() {
            api.amd = true;
            return api;
        });
    }

    // Create a "rangy" property of the global object in any case. Other Rangy modules (which use Rangy's own simple
    // module system) rely on the existence of this global property
    global.rangy = api;
})(this);

rangy.createCoreModule("DomUtil", [], function(api, module) {
    var UNDEF = "undefined";
    var util = api.util;

    // Perform feature tests
    if (!util.areHostMethods(document, ["createDocumentFragment", "createElement", "createTextNode"])) {
        module.fail("document missing a Node creation method");
    }

    if (!util.isHostMethod(document, "getElementsByTagName")) {
        module.fail("document missing getElementsByTagName method");
    }

    var el = document.createElement("div");
    if (!util.areHostMethods(el, ["insertBefore", "appendChild", "cloneNode"] ||
            !util.areHostObjects(el, ["previousSibling", "nextSibling", "childNodes", "parentNode"]))) {
        module.fail("Incomplete Element implementation");
    }

    // innerHTML is required for Range's createContextualFragment method
    if (!util.isHostProperty(el, "innerHTML")) {
        module.fail("Element is missing innerHTML property");
    }

    var textNode = document.createTextNode("test");
    if (!util.areHostMethods(textNode, ["splitText", "deleteData", "insertData", "appendData", "cloneNode"] ||
            !util.areHostObjects(el, ["previousSibling", "nextSibling", "childNodes", "parentNode"]) ||
            !util.areHostProperties(textNode, ["data"]))) {
        module.fail("Incomplete Text Node implementation");
    }

    /*----------------------------------------------------------------------------------------------------------------*/

    // Removed use of indexOf because of a bizarre bug in Opera that is thrown in one of the Acid3 tests. I haven't been
    // able to replicate it outside of the test. The bug is that indexOf returns -1 when called on an Array that
    // contains just the document as a single element and the value searched for is the document.
    var arrayContains = /*Array.prototype.indexOf ?
        function(arr, val) {
            return arr.indexOf(val) > -1;
        }:*/

        function(arr, val) {
            var i = arr.length;
            while (i--) {
                if (arr[i] === val) {
                    return true;
                }
            }
            return false;
        };

    // Opera 11 puts HTML elements in the null namespace, it seems, and IE 7 has undefined namespaceURI
    function isHtmlNamespace(node) {
        var ns;
        return typeof node.namespaceURI == UNDEF || ((ns = node.namespaceURI) === null || ns == "http://www.w3.org/1999/xhtml");
    }

    function parentElement(node) {
        var parent = node.parentNode;
        return (parent.nodeType == 1) ? parent : null;
    }

    function getNodeIndex(node) {
        var i = 0;
        while( (node = node.previousSibling) ) {
            ++i;
        }
        return i;
    }

    function getNodeLength(node) {
        switch (node.nodeType) {
            case 7:
            case 10:
                return 0;
            case 3:
            case 8:
                return node.length;
            default:
                return node.childNodes.length;
        }
    }

    function getCommonAncestor(node1, node2) {
        var ancestors = [], n;
        for (n = node1; n; n = n.parentNode) {
            ancestors.push(n);
        }

        for (n = node2; n; n = n.parentNode) {
            if (arrayContains(ancestors, n)) {
                return n;
            }
        }

        return null;
    }

    function isAncestorOf(ancestor, descendant, selfIsAncestor) {
        var n = selfIsAncestor ? descendant : descendant.parentNode;
        while (n) {
            if (n === ancestor) {
                return true;
            } else {
                n = n.parentNode;
            }
        }
        return false;
    }

    function isOrIsAncestorOf(ancestor, descendant) {
        return isAncestorOf(ancestor, descendant, true);
    }

    function getClosestAncestorIn(node, ancestor, selfIsAncestor) {
        var p, n = selfIsAncestor ? node : node.parentNode;
        while (n) {
            p = n.parentNode;
            if (p === ancestor) {
                return n;
            }
            n = p;
        }
        return null;
    }

    function isCharacterDataNode(node) {
        var t = node.nodeType;
        return t == 3 || t == 4 || t == 8 ; // Text, CDataSection or Comment
    }

    function isTextOrCommentNode(node) {
        if (!node) {
            return false;
        }
        var t = node.nodeType;
        return t == 3 || t == 8 ; // Text or Comment
    }

    function insertAfter(node, precedingNode) {
        var nextNode = precedingNode.nextSibling, parent = precedingNode.parentNode;
        if (nextNode) {
            parent.insertBefore(node, nextNode);
        } else {
            parent.appendChild(node);
        }
        return node;
    }

    // Note that we cannot use splitText() because it is bugridden in IE 9.
    function splitDataNode(node, index, positionsToPreserve) {
        var newNode = node.cloneNode(false);
        newNode.deleteData(0, index);
        node.deleteData(index, node.length - index);
        insertAfter(newNode, node);

        // Preserve positions
        if (positionsToPreserve) {
            for (var i = 0, position; position = positionsToPreserve[i++]; ) {
                // Handle case where position was inside the portion of node after the split point
                if (position.node == node && position.offset > index) {
                    position.node = newNode;
                    position.offset -= index;
                }
                // Handle the case where the position is a node offset within node's parent
                else if (position.node == node.parentNode && position.offset > getNodeIndex(node)) {
                    ++position.offset;
                }
            }
        }
        return newNode;
    }

    function getDocument(node) {
        if (node.nodeType == 9) {
            return node;
        } else if (typeof node.ownerDocument != UNDEF) {
            return node.ownerDocument;
        } else if (typeof node.document != UNDEF) {
            return node.document;
        } else if (node.parentNode) {
            return getDocument(node.parentNode);
        } else {
            throw module.createError("getDocument: no document found for node");
        }
    }

    function getWindow(node) {
        var doc = getDocument(node);
        if (typeof doc.defaultView != UNDEF) {
            return doc.defaultView;
        } else if (typeof doc.parentWindow != UNDEF) {
            return doc.parentWindow;
        } else {
            throw module.createError("Cannot get a window object for node");
        }
    }

    function getIframeDocument(iframeEl) {
        if (typeof iframeEl.contentDocument != UNDEF) {
            return iframeEl.contentDocument;
        } else if (typeof iframeEl.contentWindow != UNDEF) {
            return iframeEl.contentWindow.document;
        } else {
            throw module.createError("getIframeDocument: No Document object found for iframe element");
        }
    }

    function getIframeWindow(iframeEl) {
        if (typeof iframeEl.contentWindow != UNDEF) {
            return iframeEl.contentWindow;
        } else if (typeof iframeEl.contentDocument != UNDEF) {
            return iframeEl.contentDocument.defaultView;
        } else {
            throw module.createError("getIframeWindow: No Window object found for iframe element");
        }
    }

    // This looks bad. Is it worth it?
    function isWindow(obj) {
        return obj && util.isHostMethod(obj, "setTimeout") && util.isHostObject(obj, "document");
    }

    function getContentDocument(obj, module, methodName) {
        var doc;

        if (!obj) {
            doc = document;
        }

        // Test if a DOM node has been passed and obtain a document object for it if so
        else if (util.isHostProperty(obj, "nodeType")) {
            doc = (obj.nodeType == 1 && obj.tagName.toLowerCase() == "iframe")
                ? getIframeDocument(obj) : getDocument(obj);
        }

        // Test if the doc parameter appears to be a Window object
        else if (isWindow(obj)) {
            doc = obj.document;
        }

        if (!doc) {
            throw module.createError(methodName + "(): Parameter must be a Window object or DOM node");
        }

        return doc;
    }

    function getRootContainer(node) {
        var parent;
        while ( (parent = node.parentNode) ) {
            node = parent;
        }
        return node;
    }

    function comparePoints(nodeA, offsetA, nodeB, offsetB) {
        // See http://www.w3.org/TR/DOM-Level-2-Traversal-Range/ranges.html#Level-2-Range-Comparing
        var nodeC, root, childA, childB, n;
        if (nodeA == nodeB) {
            // Case 1: nodes are the same
            return offsetA === offsetB ? 0 : (offsetA < offsetB) ? -1 : 1;
        } else if ( (nodeC = getClosestAncestorIn(nodeB, nodeA, true)) ) {
            // Case 2: node C (container B or an ancestor) is a child node of A
            return offsetA <= getNodeIndex(nodeC) ? -1 : 1;
        } else if ( (nodeC = getClosestAncestorIn(nodeA, nodeB, true)) ) {
            // Case 3: node C (container A or an ancestor) is a child node of B
            return getNodeIndex(nodeC) < offsetB  ? -1 : 1;
        } else {
            root = getCommonAncestor(nodeA, nodeB);
            if (!root) {
                throw new Error("comparePoints error: nodes have no common ancestor");
            }

            // Case 4: containers are siblings or descendants of siblings
            childA = (nodeA === root) ? root : getClosestAncestorIn(nodeA, root, true);
            childB = (nodeB === root) ? root : getClosestAncestorIn(nodeB, root, true);

            if (childA === childB) {
                // This shouldn't be possible
                throw module.createError("comparePoints got to case 4 and childA and childB are the same!");
            } else {
                n = root.firstChild;
                while (n) {
                    if (n === childA) {
                        return -1;
                    } else if (n === childB) {
                        return 1;
                    }
                    n = n.nextSibling;
                }
            }
        }
    }

    /*----------------------------------------------------------------------------------------------------------------*/

    // Test for IE's crash (IE 6/7) or exception (IE >= 8) when a reference to garbage-collected text node is queried
    var crashyTextNodes = false;

    function isBrokenNode(node) {
        try {
            node.parentNode;
            return false;
        } catch (e) {
            return true;
        }
    }

    (function() {
        var el = document.createElement("b");
        el.innerHTML = "1";
        var textNode = el.firstChild;
        el.innerHTML = "<br>";
        crashyTextNodes = isBrokenNode(textNode);

        api.features.crashyTextNodes = crashyTextNodes;
    })();

    /*----------------------------------------------------------------------------------------------------------------*/

    function inspectNode(node) {
        if (!node) {
            return "[No node]";
        }
        if (crashyTextNodes && isBrokenNode(node)) {
            return "[Broken node]";
        }
        if (isCharacterDataNode(node)) {
            return '"' + node.data + '"';
        }
        if (node.nodeType == 1) {
            var idAttr = node.id ? ' id="' + node.id + '"' : "";
            return "<" + node.nodeName + idAttr + ">[" + getNodeIndex(node) + "][" + node.childNodes.length + "][" + (node.innerHTML || "[innerHTML not supported]").slice(0, 25) + "]";
        }
        return node.nodeName;
    }

    function fragmentFromNodeChildren(node) {
        var fragment = getDocument(node).createDocumentFragment(), child;
        while ( (child = node.firstChild) ) {
            fragment.appendChild(child);
        }
        return fragment;
    }

    var getComputedStyleProperty;
    if (typeof window.getComputedStyle != UNDEF) {
        getComputedStyleProperty = function(el, propName) {
            return getWindow(el).getComputedStyle(el, null)[propName];
        };
    } else if (typeof document.documentElement.currentStyle != UNDEF) {
        getComputedStyleProperty = function(el, propName) {
            return el.currentStyle[propName];
        };
    } else {
        module.fail("No means of obtaining computed style properties found");
    }

    function NodeIterator(root) {
        this.root = root;
        this._next = root;
    }

    NodeIterator.prototype = {
        _current: null,

        hasNext: function() {
            return !!this._next;
        },

        next: function() {
            var n = this._current = this._next;
            var child, next;
            if (this._current) {
                child = n.firstChild;
                if (child) {
                    this._next = child;
                } else {
                    next = null;
                    while ((n !== this.root) && !(next = n.nextSibling)) {
                        n = n.parentNode;
                    }
                    this._next = next;
                }
            }
            return this._current;
        },

        detach: function() {
            this._current = this._next = this.root = null;
        }
    };

    function createIterator(root) {
        return new NodeIterator(root);
    }

    function DomPosition(node, offset) {
        this.node = node;
        this.offset = offset;
    }

    DomPosition.prototype = {
        equals: function(pos) {
            return !!pos && this.node === pos.node && this.offset == pos.offset;
        },

        inspect: function() {
            return "[DomPosition(" + inspectNode(this.node) + ":" + this.offset + ")]";
        },

        toString: function() {
            return this.inspect();
        }
    };

    function DOMException(codeName) {
        this.code = this[codeName];
        this.codeName = codeName;
        this.message = "DOMException: " + this.codeName;
    }

    DOMException.prototype = {
        INDEX_SIZE_ERR: 1,
        HIERARCHY_REQUEST_ERR: 3,
        WRONG_DOCUMENT_ERR: 4,
        NO_MODIFICATION_ALLOWED_ERR: 7,
        NOT_FOUND_ERR: 8,
        NOT_SUPPORTED_ERR: 9,
        INVALID_STATE_ERR: 11
    };

    DOMException.prototype.toString = function() {
        return this.message;
    };

    api.dom = {
        arrayContains: arrayContains,
        isHtmlNamespace: isHtmlNamespace,
        parentElement: parentElement,
        getNodeIndex: getNodeIndex,
        getNodeLength: getNodeLength,
        getCommonAncestor: getCommonAncestor,
        isAncestorOf: isAncestorOf,
        isOrIsAncestorOf: isOrIsAncestorOf,
        getClosestAncestorIn: getClosestAncestorIn,
        isCharacterDataNode: isCharacterDataNode,
        isTextOrCommentNode: isTextOrCommentNode,
        insertAfter: insertAfter,
        splitDataNode: splitDataNode,
        getDocument: getDocument,
        getWindow: getWindow,
        getIframeWindow: getIframeWindow,
        getIframeDocument: getIframeDocument,
        getBody: util.getBody,
        isWindow: isWindow,
        getContentDocument: getContentDocument,
        getRootContainer: getRootContainer,
        comparePoints: comparePoints,
        isBrokenNode: isBrokenNode,
        inspectNode: inspectNode,
        getComputedStyleProperty: getComputedStyleProperty,
        fragmentFromNodeChildren: fragmentFromNodeChildren,
        createIterator: createIterator,
        DomPosition: DomPosition
    };

    api.DOMException = DOMException;
});
rangy.createCoreModule("DomRange", ["DomUtil"], function(api, module) {
    var dom = api.dom;
    var util = api.util;
    var DomPosition = dom.DomPosition;
    var DOMException = api.DOMException;

    var isCharacterDataNode = dom.isCharacterDataNode;
    var getNodeIndex = dom.getNodeIndex;
    var isOrIsAncestorOf = dom.isOrIsAncestorOf;
    var getDocument = dom.getDocument;
    var comparePoints = dom.comparePoints;
    var splitDataNode = dom.splitDataNode;
    var getClosestAncestorIn = dom.getClosestAncestorIn;
    var getNodeLength = dom.getNodeLength;
    var arrayContains = dom.arrayContains;
    var getRootContainer = dom.getRootContainer;
    var crashyTextNodes = api.features.crashyTextNodes;

    /*----------------------------------------------------------------------------------------------------------------*/

    // Utility functions

    function isNonTextPartiallySelected(node, range) {
        return (node.nodeType != 3) &&
               (isOrIsAncestorOf(node, range.startContainer) || isOrIsAncestorOf(node, range.endContainer));
    }

    function getRangeDocument(range) {
        return range.document || getDocument(range.startContainer);
    }

    function getBoundaryBeforeNode(node) {
        return new DomPosition(node.parentNode, getNodeIndex(node));
    }

    function getBoundaryAfterNode(node) {
        return new DomPosition(node.parentNode, getNodeIndex(node) + 1);
    }

    function insertNodeAtPosition(node, n, o) {
        var firstNodeInserted = node.nodeType == 11 ? node.firstChild : node;
        if (isCharacterDataNode(n)) {
            if (o == n.length) {
                dom.insertAfter(node, n);
            } else {
                n.parentNode.insertBefore(node, o == 0 ? n : splitDataNode(n, o));
            }
        } else if (o >= n.childNodes.length) {
            n.appendChild(node);
        } else {
            n.insertBefore(node, n.childNodes[o]);
        }
        return firstNodeInserted;
    }

    function rangesIntersect(rangeA, rangeB, touchingIsIntersecting) {
        assertRangeValid(rangeA);
        assertRangeValid(rangeB);

        if (getRangeDocument(rangeB) != getRangeDocument(rangeA)) {
            throw new DOMException("WRONG_DOCUMENT_ERR");
        }

        var startComparison = comparePoints(rangeA.startContainer, rangeA.startOffset, rangeB.endContainer, rangeB.endOffset),
            endComparison = comparePoints(rangeA.endContainer, rangeA.endOffset, rangeB.startContainer, rangeB.startOffset);

        return touchingIsIntersecting ? startComparison <= 0 && endComparison >= 0 : startComparison < 0 && endComparison > 0;
    }

    function cloneSubtree(iterator) {
        var partiallySelected;
        for (var node, frag = getRangeDocument(iterator.range).createDocumentFragment(), subIterator; node = iterator.next(); ) {
            partiallySelected = iterator.isPartiallySelectedSubtree();
            node = node.cloneNode(!partiallySelected);
            if (partiallySelected) {
                subIterator = iterator.getSubtreeIterator();
                node.appendChild(cloneSubtree(subIterator));
                subIterator.detach(true);
            }

            if (node.nodeType == 10) { // DocumentType
                throw new DOMException("HIERARCHY_REQUEST_ERR");
            }
            frag.appendChild(node);
        }
        return frag;
    }

    function iterateSubtree(rangeIterator, func, iteratorState) {
        var it, n;
        iteratorState = iteratorState || { stop: false };
        for (var node, subRangeIterator; node = rangeIterator.next(); ) {
            if (rangeIterator.isPartiallySelectedSubtree()) {
                if (func(node) === false) {
                    iteratorState.stop = true;
                    return;
                } else {
                    // The node is partially selected by the Range, so we can use a new RangeIterator on the portion of
                    // the node selected by the Range.
                    subRangeIterator = rangeIterator.getSubtreeIterator();
                    iterateSubtree(subRangeIterator, func, iteratorState);
                    subRangeIterator.detach(true);
                    if (iteratorState.stop) {
                        return;
                    }
                }
            } else {
                // The whole node is selected, so we can use efficient DOM iteration to iterate over the node and its
                // descendants
                it = dom.createIterator(node);
                while ( (n = it.next()) ) {
                    if (func(n) === false) {
                        iteratorState.stop = true;
                        return;
                    }
                }
            }
        }
    }

    function deleteSubtree(iterator) {
        var subIterator;
        while (iterator.next()) {
            if (iterator.isPartiallySelectedSubtree()) {
                subIterator = iterator.getSubtreeIterator();
                deleteSubtree(subIterator);
                subIterator.detach(true);
            } else {
                iterator.remove();
            }
        }
    }

    function extractSubtree(iterator) {
        for (var node, frag = getRangeDocument(iterator.range).createDocumentFragment(), subIterator; node = iterator.next(); ) {

            if (iterator.isPartiallySelectedSubtree()) {
                node = node.cloneNode(false);
                subIterator = iterator.getSubtreeIterator();
                node.appendChild(extractSubtree(subIterator));
                subIterator.detach(true);
            } else {
                iterator.remove();
            }
            if (node.nodeType == 10) { // DocumentType
                throw new DOMException("HIERARCHY_REQUEST_ERR");
            }
            frag.appendChild(node);
        }
        return frag;
    }

    function getNodesInRange(range, nodeTypes, filter) {
        var filterNodeTypes = !!(nodeTypes && nodeTypes.length), regex;
        var filterExists = !!filter;
        if (filterNodeTypes) {
            regex = new RegExp("^(" + nodeTypes.join("|") + ")$");
        }

        var nodes = [];
        iterateSubtree(new RangeIterator(range, false), function(node) {
            if (filterNodeTypes && !regex.test(node.nodeType)) {
                return;
            }
            if (filterExists && !filter(node)) {
                return;
            }
            // Don't include a boundary container if it is a character data node and the range does not contain any
            // of its character data. See issue 190.
            var sc = range.startContainer;
            if (node == sc && isCharacterDataNode(sc) && range.startOffset == sc.length) {
                return;
            }

            var ec = range.endContainer;
            if (node == ec && isCharacterDataNode(ec) && range.endOffset == 0) {
                return;
            }

            nodes.push(node);
        });
        return nodes;
    }

    function inspect(range) {
        var name = (typeof range.getName == "undefined") ? "Range" : range.getName();
        return "[" + name + "(" + dom.inspectNode(range.startContainer) + ":" + range.startOffset + ", " +
                dom.inspectNode(range.endContainer) + ":" + range.endOffset + ")]";
    }

    /*----------------------------------------------------------------------------------------------------------------*/

    // RangeIterator code partially borrows from IERange by Tim Ryan (http://github.com/timcameronryan/IERange)

    function RangeIterator(range, clonePartiallySelectedTextNodes) {
        this.range = range;
        this.clonePartiallySelectedTextNodes = clonePartiallySelectedTextNodes;


        if (!range.collapsed) {
            this.sc = range.startContainer;
            this.so = range.startOffset;
            this.ec = range.endContainer;
            this.eo = range.endOffset;
            var root = range.commonAncestorContainer;

            if (this.sc === this.ec && isCharacterDataNode(this.sc)) {
                this.isSingleCharacterDataNode = true;
                this._first = this._last = this._next = this.sc;
            } else {
                this._first = this._next = (this.sc === root && !isCharacterDataNode(this.sc)) ?
                    this.sc.childNodes[this.so] : getClosestAncestorIn(this.sc, root, true);
                this._last = (this.ec === root && !isCharacterDataNode(this.ec)) ?
                    this.ec.childNodes[this.eo - 1] : getClosestAncestorIn(this.ec, root, true);
            }
        }
    }

    RangeIterator.prototype = {
        _current: null,
        _next: null,
        _first: null,
        _last: null,
        isSingleCharacterDataNode: false,

        reset: function() {
            this._current = null;
            this._next = this._first;
        },

        hasNext: function() {
            return !!this._next;
        },

        next: function() {
            // Move to next node
            var current = this._current = this._next;
            if (current) {
                this._next = (current !== this._last) ? current.nextSibling : null;

                // Check for partially selected text nodes
                if (isCharacterDataNode(current) && this.clonePartiallySelectedTextNodes) {
                    if (current === this.ec) {
                        (current = current.cloneNode(true)).deleteData(this.eo, current.length - this.eo);
                    }
                    if (this._current === this.sc) {
                        (current = current.cloneNode(true)).deleteData(0, this.so);
                    }
                }
            }

            return current;
        },

        remove: function() {
            var current = this._current, start, end;

            if (isCharacterDataNode(current) && (current === this.sc || current === this.ec)) {
                start = (current === this.sc) ? this.so : 0;
                end = (current === this.ec) ? this.eo : current.length;
                if (start != end) {
                    current.deleteData(start, end - start);
                }
            } else {
                if (current.parentNode) {
                    current.parentNode.removeChild(current);
                } else {
                }
            }
        },

        // Checks if the current node is partially selected
        isPartiallySelectedSubtree: function() {
            var current = this._current;
            return isNonTextPartiallySelected(current, this.range);
        },

        getSubtreeIterator: function() {
            var subRange;
            if (this.isSingleCharacterDataNode) {
                subRange = this.range.cloneRange();
                subRange.collapse(false);
            } else {
                subRange = new Range(getRangeDocument(this.range));
                var current = this._current;
                var startContainer = current, startOffset = 0, endContainer = current, endOffset = getNodeLength(current);

                if (isOrIsAncestorOf(current, this.sc)) {
                    startContainer = this.sc;
                    startOffset = this.so;
                }
                if (isOrIsAncestorOf(current, this.ec)) {
                    endContainer = this.ec;
                    endOffset = this.eo;
                }

                updateBoundaries(subRange, startContainer, startOffset, endContainer, endOffset);
            }
            return new RangeIterator(subRange, this.clonePartiallySelectedTextNodes);
        },

        detach: function(detachRange) {
            if (detachRange) {
                this.range.detach();
            }
            this.range = this._current = this._next = this._first = this._last = this.sc = this.so = this.ec = this.eo = null;
        }
    };

    /*----------------------------------------------------------------------------------------------------------------*/

    // Exceptions

    function RangeException(codeName) {
        this.code = this[codeName];
        this.codeName = codeName;
        this.message = "RangeException: " + this.codeName;
    }

    RangeException.prototype = {
        BAD_BOUNDARYPOINTS_ERR: 1,
        INVALID_NODE_TYPE_ERR: 2
    };

    RangeException.prototype.toString = function() {
        return this.message;
    };

    /*----------------------------------------------------------------------------------------------------------------*/

    var beforeAfterNodeTypes = [1, 3, 4, 5, 7, 8, 10];
    var rootContainerNodeTypes = [2, 9, 11];
    var readonlyNodeTypes = [5, 6, 10, 12];
    var insertableNodeTypes = [1, 3, 4, 5, 7, 8, 10, 11];
    var surroundNodeTypes = [1, 3, 4, 5, 7, 8];

    function createAncestorFinder(nodeTypes) {
        return function(node, selfIsAncestor) {
            var t, n = selfIsAncestor ? node : node.parentNode;
            while (n) {
                t = n.nodeType;
                if (arrayContains(nodeTypes, t)) {
                    return n;
                }
                n = n.parentNode;
            }
            return null;
        };
    }

    var getDocumentOrFragmentContainer = createAncestorFinder( [9, 11] );
    var getReadonlyAncestor = createAncestorFinder(readonlyNodeTypes);
    var getDocTypeNotationEntityAncestor = createAncestorFinder( [6, 10, 12] );

    function assertNoDocTypeNotationEntityAncestor(node, allowSelf) {
        if (getDocTypeNotationEntityAncestor(node, allowSelf)) {
            throw new RangeException("INVALID_NODE_TYPE_ERR");
        }
    }

    function assertNotDetached(range) {
        if (!range.startContainer) {
            throw new DOMException("INVALID_STATE_ERR");
        }
    }

    function assertValidNodeType(node, invalidTypes) {
        if (!arrayContains(invalidTypes, node.nodeType)) {
            throw new RangeException("INVALID_NODE_TYPE_ERR");
        }
    }

    function assertValidOffset(node, offset) {
        if (offset < 0 || offset > (isCharacterDataNode(node) ? node.length : node.childNodes.length)) {
            throw new DOMException("INDEX_SIZE_ERR");
        }
    }

    function assertSameDocumentOrFragment(node1, node2) {
        if (getDocumentOrFragmentContainer(node1, true) !== getDocumentOrFragmentContainer(node2, true)) {
            throw new DOMException("WRONG_DOCUMENT_ERR");
        }
    }

    function assertNodeNotReadOnly(node) {
        if (getReadonlyAncestor(node, true)) {
            throw new DOMException("NO_MODIFICATION_ALLOWED_ERR");
        }
    }

    function assertNode(node, codeName) {
        if (!node) {
            throw new DOMException(codeName);
        }
    }

    function isOrphan(node) {
        return (crashyTextNodes && dom.isBrokenNode(node)) ||
            !arrayContains(rootContainerNodeTypes, node.nodeType) && !getDocumentOrFragmentContainer(node, true);
    }

    function isValidOffset(node, offset) {
        return offset <= (isCharacterDataNode(node) ? node.length : node.childNodes.length);
    }

    function isRangeValid(range) {
        return (!!range.startContainer && !!range.endContainer
                && !isOrphan(range.startContainer)
                && !isOrphan(range.endContainer)
                && isValidOffset(range.startContainer, range.startOffset)
                && isValidOffset(range.endContainer, range.endOffset));
    }

    function assertRangeValid(range) {
        assertNotDetached(range);
        if (!isRangeValid(range)) {
            throw new Error("Range error: Range is no longer valid after DOM mutation (" + range.inspect() + ")");
        }
    }

    /*----------------------------------------------------------------------------------------------------------------*/

    // Test the browser's innerHTML support to decide how to implement createContextualFragment
    var styleEl = document.createElement("style");
    var htmlParsingConforms = false;
    try {
        styleEl.innerHTML = "<b>x</b>";
        htmlParsingConforms = (styleEl.firstChild.nodeType == 3); // Opera incorrectly creates an element node
    } catch (e) {
        // IE 6 and 7 throw
    }

    api.features.htmlParsingConforms = htmlParsingConforms;

    var createContextualFragment = htmlParsingConforms ?

        // Implementation as per HTML parsing spec, trusting in the browser's implementation of innerHTML. See
        // discussion and base code for this implementation at issue 67.
        // Spec: http://html5.org/specs/dom-parsing.html#extensions-to-the-range-interface
        // Thanks to Aleks Williams.
        function(fragmentStr) {
            // "Let node the context object's start's node."
            var node = this.startContainer;
            var doc = getDocument(node);

            // "If the context object's start's node is null, raise an INVALID_STATE_ERR
            // exception and abort these steps."
            if (!node) {
                throw new DOMException("INVALID_STATE_ERR");
            }

            // "Let element be as follows, depending on node's interface:"
            // Document, Document Fragment: null
            var el = null;

            // "Element: node"
            if (node.nodeType == 1) {
                el = node;

            // "Text, Comment: node's parentElement"
            } else if (isCharacterDataNode(node)) {
                el = dom.parentElement(node);
            }

            // "If either element is null or element's ownerDocument is an HTML document
            // and element's local name is "html" and element's namespace is the HTML
            // namespace"
            if (el === null || (
                el.nodeName == "HTML"
                && dom.isHtmlNamespace(getDocument(el).documentElement)
                && dom.isHtmlNamespace(el)
            )) {

            // "let element be a new Element with "body" as its local name and the HTML
            // namespace as its namespace.""
                el = doc.createElement("body");
            } else {
                el = el.cloneNode(false);
            }

            // "If the node's document is an HTML document: Invoke the HTML fragment parsing algorithm."
            // "If the node's document is an XML document: Invoke the XML fragment parsing algorithm."
            // "In either case, the algorithm must be invoked with fragment as the input
            // and element as the context element."
            el.innerHTML = fragmentStr;

            // "If this raises an exception, then abort these steps. Otherwise, let new
            // children be the nodes returned."

            // "Let fragment be a new DocumentFragment."
            // "Append all new children to fragment."
            // "Return fragment."
            return dom.fragmentFromNodeChildren(el);
        } :

        // In this case, innerHTML cannot be trusted, so fall back to a simpler, non-conformant implementation that
        // previous versions of Rangy used (with the exception of using a body element rather than a div)
        function(fragmentStr) {
            assertNotDetached(this);
            var doc = getRangeDocument(this);
            var el = doc.createElement("body");
            el.innerHTML = fragmentStr;

            return dom.fragmentFromNodeChildren(el);
        };

    function splitRangeBoundaries(range, positionsToPreserve) {
        assertRangeValid(range);

        var sc = range.startContainer, so = range.startOffset, ec = range.endContainer, eo = range.endOffset;
        var startEndSame = (sc === ec);

        if (isCharacterDataNode(ec) && eo > 0 && eo < ec.length) {
            splitDataNode(ec, eo, positionsToPreserve);
        }

        if (isCharacterDataNode(sc) && so > 0 && so < sc.length) {
            sc = splitDataNode(sc, so, positionsToPreserve);
            if (startEndSame) {
                eo -= so;
                ec = sc;
            } else if (ec == sc.parentNode && eo >= getNodeIndex(sc)) {
                eo++;
            }
            so = 0;
        }
        range.setStartAndEnd(sc, so, ec, eo);
    }

    /*----------------------------------------------------------------------------------------------------------------*/

    var rangeProperties = ["startContainer", "startOffset", "endContainer", "endOffset", "collapsed",
        "commonAncestorContainer"];

    var s2s = 0, s2e = 1, e2e = 2, e2s = 3;
    var n_b = 0, n_a = 1, n_b_a = 2, n_i = 3;

    util.extend(api.rangePrototype, {
        compareBoundaryPoints: function(how, range) {
            assertRangeValid(this);
            assertSameDocumentOrFragment(this.startContainer, range.startContainer);

            var nodeA, offsetA, nodeB, offsetB;
            var prefixA = (how == e2s || how == s2s) ? "start" : "end";
            var prefixB = (how == s2e || how == s2s) ? "start" : "end";
            nodeA = this[prefixA + "Container"];
            offsetA = this[prefixA + "Offset"];
            nodeB = range[prefixB + "Container"];
            offsetB = range[prefixB + "Offset"];
            return comparePoints(nodeA, offsetA, nodeB, offsetB);
        },

        insertNode: function(node) {
            assertRangeValid(this);
            assertValidNodeType(node, insertableNodeTypes);
            assertNodeNotReadOnly(this.startContainer);

            if (isOrIsAncestorOf(node, this.startContainer)) {
                throw new DOMException("HIERARCHY_REQUEST_ERR");
            }

            // No check for whether the container of the start of the Range is of a type that does not allow
            // children of the type of node: the browser's DOM implementation should do this for us when we attempt
            // to add the node

            var firstNodeInserted = insertNodeAtPosition(node, this.startContainer, this.startOffset);
            this.setStartBefore(firstNodeInserted);
        },

        cloneContents: function() {
            assertRangeValid(this);

            var clone, frag;
            if (this.collapsed) {
                return getRangeDocument(this).createDocumentFragment();
            } else {
                if (this.startContainer === this.endContainer && isCharacterDataNode(this.startContainer)) {
                    clone = this.startContainer.cloneNode(true);
                    clone.data = clone.data.slice(this.startOffset, this.endOffset);
                    frag = getRangeDocument(this).createDocumentFragment();
                    frag.appendChild(clone);
                    return frag;
                } else {
                    var iterator = new RangeIterator(this, true);
                    clone = cloneSubtree(iterator);
                    iterator.detach();
                }
                return clone;
            }
        },

        canSurroundContents: function() {
            assertRangeValid(this);
            assertNodeNotReadOnly(this.startContainer);
            assertNodeNotReadOnly(this.endContainer);

            // Check if the contents can be surrounded. Specifically, this means whether the range partially selects
            // no non-text nodes.
            var iterator = new RangeIterator(this, true);
            var boundariesInvalid = (iterator._first && (isNonTextPartiallySelected(iterator._first, this)) ||
                    (iterator._last && isNonTextPartiallySelected(iterator._last, this)));
            iterator.detach();
            return !boundariesInvalid;
        },

        surroundContents: function(node) {
            assertValidNodeType(node, surroundNodeTypes);

            if (!this.canSurroundContents()) {
                throw new RangeException("BAD_BOUNDARYPOINTS_ERR");
            }

            // Extract the contents
            var content = this.extractContents();

            // Clear the children of the node
            if (node.hasChildNodes()) {
                while (node.lastChild) {
                    node.removeChild(node.lastChild);
                }
            }

            // Insert the new node and add the extracted contents
            insertNodeAtPosition(node, this.startContainer, this.startOffset);
            node.appendChild(content);

            this.selectNode(node);
        },

        cloneRange: function() {
            assertRangeValid(this);
            var range = new Range(getRangeDocument(this));
            var i = rangeProperties.length, prop;
            while (i--) {
                prop = rangeProperties[i];
                range[prop] = this[prop];
            }
            return range;
        },

        toString: function() {
            assertRangeValid(this);
            var sc = this.startContainer;
            if (sc === this.endContainer && isCharacterDataNode(sc)) {
                return (sc.nodeType == 3 || sc.nodeType == 4) ? sc.data.slice(this.startOffset, this.endOffset) : "";
            } else {
                var textParts = [], iterator = new RangeIterator(this, true);
                iterateSubtree(iterator, function(node) {
                    // Accept only text or CDATA nodes, not comments
                    if (node.nodeType == 3 || node.nodeType == 4) {
                        textParts.push(node.data);
                    }
                });
                iterator.detach();
                return textParts.join("");
            }
        },

        // The methods below are all non-standard. The following batch were introduced by Mozilla but have since
        // been removed from Mozilla.

        compareNode: function(node) {
            assertRangeValid(this);

            var parent = node.parentNode;
            var nodeIndex = getNodeIndex(node);

            if (!parent) {
                throw new DOMException("NOT_FOUND_ERR");
            }

            var startComparison = this.comparePoint(parent, nodeIndex),
                endComparison = this.comparePoint(parent, nodeIndex + 1);

            if (startComparison < 0) { // Node starts before
                return (endComparison > 0) ? n_b_a : n_b;
            } else {
                return (endComparison > 0) ? n_a : n_i;
            }
        },

        comparePoint: function(node, offset) {
            assertRangeValid(this);
            assertNode(node, "HIERARCHY_REQUEST_ERR");
            assertSameDocumentOrFragment(node, this.startContainer);

            if (comparePoints(node, offset, this.startContainer, this.startOffset) < 0) {
                return -1;
            } else if (comparePoints(node, offset, this.endContainer, this.endOffset) > 0) {
                return 1;
            }
            return 0;
        },

        createContextualFragment: createContextualFragment,

        toHtml: function() {
            assertRangeValid(this);
            var container = this.commonAncestorContainer.parentNode.cloneNode(false);
            container.appendChild(this.cloneContents());
            return container.innerHTML;
        },

        // touchingIsIntersecting determines whether this method considers a node that borders a range intersects
        // with it (as in WebKit) or not (as in Gecko pre-1.9, and the default)
        intersectsNode: function(node, touchingIsIntersecting) {
            assertRangeValid(this);
            assertNode(node, "NOT_FOUND_ERR");
            if (getDocument(node) !== getRangeDocument(this)) {
                return false;
            }

            var parent = node.parentNode, offset = getNodeIndex(node);
            assertNode(parent, "NOT_FOUND_ERR");

            var startComparison = comparePoints(parent, offset, this.endContainer, this.endOffset),
                endComparison = comparePoints(parent, offset + 1, this.startContainer, this.startOffset);

            return touchingIsIntersecting ? startComparison <= 0 && endComparison >= 0 : startComparison < 0 && endComparison > 0;
        },

        isPointInRange: function(node, offset) {
            assertRangeValid(this);
            assertNode(node, "HIERARCHY_REQUEST_ERR");
            assertSameDocumentOrFragment(node, this.startContainer);

            return (comparePoints(node, offset, this.startContainer, this.startOffset) >= 0) &&
                   (comparePoints(node, offset, this.endContainer, this.endOffset) <= 0);
        },

        // The methods below are non-standard and invented by me.

        // Sharing a boundary start-to-end or end-to-start does not count as intersection.
        intersectsRange: function(range) {
            return rangesIntersect(this, range, false);
        },

        // Sharing a boundary start-to-end or end-to-start does count as intersection.
        intersectsOrTouchesRange: function(range) {
            return rangesIntersect(this, range, true);
        },

        intersection: function(range) {
            if (this.intersectsRange(range)) {
                var startComparison = comparePoints(this.startContainer, this.startOffset, range.startContainer, range.startOffset),
                    endComparison = comparePoints(this.endContainer, this.endOffset, range.endContainer, range.endOffset);

                var intersectionRange = this.cloneRange();
                if (startComparison == -1) {
                    intersectionRange.setStart(range.startContainer, range.startOffset);
                }
                if (endComparison == 1) {
                    intersectionRange.setEnd(range.endContainer, range.endOffset);
                }
                return intersectionRange;
            }
            return null;
        },

        union: function(range) {
            if (this.intersectsOrTouchesRange(range)) {
                var unionRange = this.cloneRange();
                if (comparePoints(range.startContainer, range.startOffset, this.startContainer, this.startOffset) == -1) {
                    unionRange.setStart(range.startContainer, range.startOffset);
                }
                if (comparePoints(range.endContainer, range.endOffset, this.endContainer, this.endOffset) == 1) {
                    unionRange.setEnd(range.endContainer, range.endOffset);
                }
                return unionRange;
            } else {
                throw new RangeException("Ranges do not intersect");
            }
        },

        containsNode: function(node, allowPartial) {
            if (allowPartial) {
                return this.intersectsNode(node, false);
            } else {
                return this.compareNode(node) == n_i;
            }
        },

        containsNodeContents: function(node) {
            return this.comparePoint(node, 0) >= 0 && this.comparePoint(node, getNodeLength(node)) <= 0;
        },

        containsRange: function(range) {
            var intersection = this.intersection(range);
            return intersection !== null && range.equals(intersection);
        },

        containsNodeText: function(node) {
            var nodeRange = this.cloneRange();
            nodeRange.selectNode(node);
            var textNodes = nodeRange.getNodes([3]);
            if (textNodes.length > 0) {
                nodeRange.setStart(textNodes[0], 0);
                var lastTextNode = textNodes.pop();
                nodeRange.setEnd(lastTextNode, lastTextNode.length);
                var contains = this.containsRange(nodeRange);
                nodeRange.detach();
                return contains;
            } else {
                return this.containsNodeContents(node);
            }
        },

        getNodes: function(nodeTypes, filter) {
            assertRangeValid(this);
            return getNodesInRange(this, nodeTypes, filter);
        },

        getDocument: function() {
            return getRangeDocument(this);
        },

        collapseBefore: function(node) {
            assertNotDetached(this);

            this.setEndBefore(node);
            this.collapse(false);
        },

        collapseAfter: function(node) {
            assertNotDetached(this);

            this.setStartAfter(node);
            this.collapse(true);
        },

        getBookmark: function(containerNode) {
            var doc = getRangeDocument(this);
            var preSelectionRange = api.createRange(doc);
            containerNode = containerNode || dom.getBody(doc);
            preSelectionRange.selectNodeContents(containerNode);
            var range = this.intersection(preSelectionRange);
            var start = 0, end = 0;
            if (range) {
                preSelectionRange.setEnd(range.startContainer, range.startOffset);
                start = preSelectionRange.toString().length;
                end = start + range.toString().length;
                preSelectionRange.detach();
            }

            return {
                start: start,
                end: end,
                containerNode: containerNode
            };
        },

        moveToBookmark: function(bookmark) {
            var containerNode = bookmark.containerNode;
            var charIndex = 0;
            this.setStart(containerNode, 0);
            this.collapse(true);
            var nodeStack = [containerNode], node, foundStart = false, stop = false;
            var nextCharIndex, i, childNodes;

            while (!stop && (node = nodeStack.pop())) {
                if (node.nodeType == 3) {
                    nextCharIndex = charIndex + node.length;
                    if (!foundStart && bookmark.start >= charIndex && bookmark.start <= nextCharIndex) {
                        this.setStart(node, bookmark.start - charIndex);
                        foundStart = true;
                    }
                    if (foundStart && bookmark.end >= charIndex && bookmark.end <= nextCharIndex) {
                        this.setEnd(node, bookmark.end - charIndex);
                        stop = true;
                    }
                    charIndex = nextCharIndex;
                } else {
                    childNodes = node.childNodes;
                    i = childNodes.length;
                    while (i--) {
                        nodeStack.push(childNodes[i]);
                    }
                }
            }
        },

        getName: function() {
            return "DomRange";
        },

        equals: function(range) {
            return Range.rangesEqual(this, range);
        },

        isValid: function() {
            return isRangeValid(this);
        },

        inspect: function() {
            return inspect(this);
        }
    });

    function copyComparisonConstantsToObject(obj) {
        obj.START_TO_START = s2s;
        obj.START_TO_END = s2e;
        obj.END_TO_END = e2e;
        obj.END_TO_START = e2s;

        obj.NODE_BEFORE = n_b;
        obj.NODE_AFTER = n_a;
        obj.NODE_BEFORE_AND_AFTER = n_b_a;
        obj.NODE_INSIDE = n_i;
    }

    function copyComparisonConstants(constructor) {
        copyComparisonConstantsToObject(constructor);
        copyComparisonConstantsToObject(constructor.prototype);
    }

    function createRangeContentRemover(remover, boundaryUpdater) {
        return function() {
            assertRangeValid(this);

            var sc = this.startContainer, so = this.startOffset, root = this.commonAncestorContainer;

            var iterator = new RangeIterator(this, true);

            // Work out where to position the range after content removal
            var node, boundary;
            if (sc !== root) {
                node = getClosestAncestorIn(sc, root, true);
                boundary = getBoundaryAfterNode(node);
                sc = boundary.node;
                so = boundary.offset;
            }

            // Check none of the range is read-only
            iterateSubtree(iterator, assertNodeNotReadOnly);

            iterator.reset();

            // Remove the content
            var returnValue = remover(iterator);
            iterator.detach();

            // Move to the new position
            boundaryUpdater(this, sc, so, sc, so);

            return returnValue;
        };
    }

    function createPrototypeRange(constructor, boundaryUpdater, detacher) {
        function createBeforeAfterNodeSetter(isBefore, isStart) {
            return function(node) {
                assertNotDetached(this);
                assertValidNodeType(node, beforeAfterNodeTypes);
                assertValidNodeType(getRootContainer(node), rootContainerNodeTypes);

                var boundary = (isBefore ? getBoundaryBeforeNode : getBoundaryAfterNode)(node);
                (isStart ? setRangeStart : setRangeEnd)(this, boundary.node, boundary.offset);
            };
        }

        function setRangeStart(range, node, offset) {
            var ec = range.endContainer, eo = range.endOffset;
            if (node !== range.startContainer || offset !== range.startOffset) {
                // Check the root containers of the range and the new boundary, and also check whether the new boundary
                // is after the current end. In either case, collapse the range to the new position
                if (getRootContainer(node) != getRootContainer(ec) || comparePoints(node, offset, ec, eo) == 1) {
                    ec = node;
                    eo = offset;
                }
                boundaryUpdater(range, node, offset, ec, eo);
            }
        }

        function setRangeEnd(range, node, offset) {
            var sc = range.startContainer, so = range.startOffset;
            if (node !== range.endContainer || offset !== range.endOffset) {
                // Check the root containers of the range and the new boundary, and also check whether the new boundary
                // is after the current end. In either case, collapse the range to the new position
                if (getRootContainer(node) != getRootContainer(sc) || comparePoints(node, offset, sc, so) == -1) {
                    sc = node;
                    so = offset;
                }
                boundaryUpdater(range, sc, so, node, offset);
            }
        }

        // Set up inheritance
        var F = function() {};
        F.prototype = api.rangePrototype;
        constructor.prototype = new F();

        util.extend(constructor.prototype, {
            setStart: function(node, offset) {
                assertNotDetached(this);
                assertNoDocTypeNotationEntityAncestor(node, true);
                assertValidOffset(node, offset);

                setRangeStart(this, node, offset);
            },

            setEnd: function(node, offset) {
                assertNotDetached(this);
                assertNoDocTypeNotationEntityAncestor(node, true);
                assertValidOffset(node, offset);

                setRangeEnd(this, node, offset);
            },

            /**
             * Convenience method to set a range's start and end boundaries. Overloaded as follows:
             * - Two parameters (node, offset) creates a collapsed range at that position
             * - Three parameters (node, startOffset, endOffset) creates a range contained with node starting at
             *   startOffset and ending at endOffset
             * - Four parameters (startNode, startOffset, endNode, endOffset) creates a range starting at startOffset in
             *   startNode and ending at endOffset in endNode
             */
            setStartAndEnd: function() {
                assertNotDetached(this);

                var args = arguments;
                var sc = args[0], so = args[1], ec = sc, eo = so;

                switch (args.length) {
                    case 3:
                        eo = args[2];
                        break;
                    case 4:
                        ec = args[2];
                        eo = args[3];
                        break;
                }

                boundaryUpdater(this, sc, so, ec, eo);
            },

            setBoundary: function(node, offset, isStart) {
                this["set" + (isStart ? "Start" : "End")](node, offset);
            },

            setStartBefore: createBeforeAfterNodeSetter(true, true),
            setStartAfter: createBeforeAfterNodeSetter(false, true),
            setEndBefore: createBeforeAfterNodeSetter(true, false),
            setEndAfter: createBeforeAfterNodeSetter(false, false),

            collapse: function(isStart) {
                assertRangeValid(this);
                if (isStart) {
                    boundaryUpdater(this, this.startContainer, this.startOffset, this.startContainer, this.startOffset);
                } else {
                    boundaryUpdater(this, this.endContainer, this.endOffset, this.endContainer, this.endOffset);
                }
            },

            selectNodeContents: function(node) {
                assertNotDetached(this);
                assertNoDocTypeNotationEntityAncestor(node, true);

                boundaryUpdater(this, node, 0, node, getNodeLength(node));
            },

            selectNode: function(node) {
                assertNotDetached(this);
                assertNoDocTypeNotationEntityAncestor(node, false);
                assertValidNodeType(node, beforeAfterNodeTypes);

                var start = getBoundaryBeforeNode(node), end = getBoundaryAfterNode(node);
                boundaryUpdater(this, start.node, start.offset, end.node, end.offset);
            },

            extractContents: createRangeContentRemover(extractSubtree, boundaryUpdater),

            deleteContents: createRangeContentRemover(deleteSubtree, boundaryUpdater),

            canSurroundContents: function() {
                assertRangeValid(this);
                assertNodeNotReadOnly(this.startContainer);
                assertNodeNotReadOnly(this.endContainer);

                // Check if the contents can be surrounded. Specifically, this means whether the range partially selects
                // no non-text nodes.
                var iterator = new RangeIterator(this, true);
                var boundariesInvalid = (iterator._first && (isNonTextPartiallySelected(iterator._first, this)) ||
                        (iterator._last && isNonTextPartiallySelected(iterator._last, this)));
                iterator.detach();
                return !boundariesInvalid;
            },

            detach: function() {
                detacher(this);
            },

            splitBoundaries: function() {
                splitRangeBoundaries(this);
            },

            splitBoundariesPreservingPositions: function(positionsToPreserve) {
                splitRangeBoundaries(this, positionsToPreserve);
            },

            normalizeBoundaries: function() {
                assertRangeValid(this);

                var sc = this.startContainer, so = this.startOffset, ec = this.endContainer, eo = this.endOffset;

                var mergeForward = function(node) {
                    var sibling = node.nextSibling;
                    if (sibling && sibling.nodeType == node.nodeType) {
                        ec = node;
                        eo = node.length;
                        node.appendData(sibling.data);
                        sibling.parentNode.removeChild(sibling);
                    }
                };

                var mergeBackward = function(node) {
                    var sibling = node.previousSibling;
                    if (sibling && sibling.nodeType == node.nodeType) {
                        sc = node;
                        var nodeLength = node.length;
                        so = sibling.length;
                        node.insertData(0, sibling.data);
                        sibling.parentNode.removeChild(sibling);
                        if (sc == ec) {
                            eo += so;
                            ec = sc;
                        } else if (ec == node.parentNode) {
                            var nodeIndex = getNodeIndex(node);
                            if (eo == nodeIndex) {
                                ec = node;
                                eo = nodeLength;
                            } else if (eo > nodeIndex) {
                                eo--;
                            }
                        }
                    }
                };

                var normalizeStart = true;

                if (isCharacterDataNode(ec)) {
                    if (ec.length == eo) {
                        mergeForward(ec);
                    }
                } else {
                    if (eo > 0) {
                        var endNode = ec.childNodes[eo - 1];
                        if (endNode && isCharacterDataNode(endNode)) {
                            mergeForward(endNode);
                        }
                    }
                    normalizeStart = !this.collapsed;
                }

                if (normalizeStart) {
                    if (isCharacterDataNode(sc)) {
                        if (so == 0) {
                            mergeBackward(sc);
                        }
                    } else {
                        if (so < sc.childNodes.length) {
                            var startNode = sc.childNodes[so];
                            if (startNode && isCharacterDataNode(startNode)) {
                                mergeBackward(startNode);
                            }
                        }
                    }
                } else {
                    sc = ec;
                    so = eo;
                }

                boundaryUpdater(this, sc, so, ec, eo);
            },

            collapseToPoint: function(node, offset) {
                assertNotDetached(this);
                assertNoDocTypeNotationEntityAncestor(node, true);
                assertValidOffset(node, offset);
                this.setStartAndEnd(node, offset);
            }
        });

        copyComparisonConstants(constructor);
    }

    /*----------------------------------------------------------------------------------------------------------------*/

    // Updates commonAncestorContainer and collapsed after boundary change
    function updateCollapsedAndCommonAncestor(range) {
        range.collapsed = (range.startContainer === range.endContainer && range.startOffset === range.endOffset);
        range.commonAncestorContainer = range.collapsed ?
            range.startContainer : dom.getCommonAncestor(range.startContainer, range.endContainer);
    }

    function updateBoundaries(range, startContainer, startOffset, endContainer, endOffset) {
        range.startContainer = startContainer;
        range.startOffset = startOffset;
        range.endContainer = endContainer;
        range.endOffset = endOffset;
        range.document = dom.getDocument(startContainer);

        updateCollapsedAndCommonAncestor(range);
    }

    function detach(range) {
        assertNotDetached(range);
        range.startContainer = range.startOffset = range.endContainer = range.endOffset = range.document = null;
        range.collapsed = range.commonAncestorContainer = null;
    }

    function Range(doc) {
        this.startContainer = doc;
        this.startOffset = 0;
        this.endContainer = doc;
        this.endOffset = 0;
        this.document = doc;
        updateCollapsedAndCommonAncestor(this);
    }

    createPrototypeRange(Range, updateBoundaries, detach);

    util.extend(Range, {
        rangeProperties: rangeProperties,
        RangeIterator: RangeIterator,
        copyComparisonConstants: copyComparisonConstants,
        createPrototypeRange: createPrototypeRange,
        inspect: inspect,
        getRangeDocument: getRangeDocument,
        rangesEqual: function(r1, r2) {
            return r1.startContainer === r2.startContainer &&
                r1.startOffset === r2.startOffset &&
                r1.endContainer === r2.endContainer &&
                r1.endOffset === r2.endOffset;
        }
    });

    api.DomRange = Range;
    api.RangeException = RangeException;
});
rangy.createCoreModule("WrappedRange", ["DomRange"], function(api, module) {
    var WrappedRange, WrappedTextRange;
    var dom = api.dom;
    var util = api.util;
    var DomPosition = dom.DomPosition;
    var DomRange = api.DomRange;
    var getBody = dom.getBody;
    var getContentDocument = dom.getContentDocument;
    var isCharacterDataNode = dom.isCharacterDataNode;


    /*----------------------------------------------------------------------------------------------------------------*/

    if (api.features.implementsDomRange) {
        // This is a wrapper around the browser's native DOM Range. It has two aims:
        // - Provide workarounds for specific browser bugs
        // - provide convenient extensions, which are inherited from Rangy's DomRange

        (function() {
            var rangeProto;
            var rangeProperties = DomRange.rangeProperties;

            function updateRangeProperties(range) {
                var i = rangeProperties.length, prop;
                while (i--) {
                    prop = rangeProperties[i];
                    range[prop] = range.nativeRange[prop];
                }
                // Fix for broken collapsed property in IE 9.
                range.collapsed = (range.startContainer === range.endContainer && range.startOffset === range.endOffset);
            }

            function updateNativeRange(range, startContainer, startOffset, endContainer, endOffset) {
                var startMoved = (range.startContainer !== startContainer || range.startOffset != startOffset);
                var endMoved = (range.endContainer !== endContainer || range.endOffset != endOffset);
                var nativeRangeDifferent = !range.equals(range.nativeRange);

                // Always set both boundaries for the benefit of IE9 (see issue 35)
                if (startMoved || endMoved || nativeRangeDifferent) {
                    range.setEnd(endContainer, endOffset);
                    range.setStart(startContainer, startOffset);
                }
            }

            function detach(range) {
                range.nativeRange.detach();
                range.detached = true;
                var i = rangeProperties.length;
                while (i--) {
                    range[ rangeProperties[i] ] = null;
                }
            }

            var createBeforeAfterNodeSetter;

            WrappedRange = function(range) {
                if (!range) {
                    throw module.createError("WrappedRange: Range must be specified");
                }
                this.nativeRange = range;
                updateRangeProperties(this);
            };

            DomRange.createPrototypeRange(WrappedRange, updateNativeRange, detach);

            rangeProto = WrappedRange.prototype;

            rangeProto.selectNode = function(node) {
                this.nativeRange.selectNode(node);
                updateRangeProperties(this);
            };

            rangeProto.cloneContents = function() {
                return this.nativeRange.cloneContents();
            };

            // Due to a long-standing Firefox bug that I have not been able to find a reliable way to detect,
            // insertNode() is never delegated to the native range.

            rangeProto.surroundContents = function(node) {
                this.nativeRange.surroundContents(node);
                updateRangeProperties(this);
            };

            rangeProto.collapse = function(isStart) {
                this.nativeRange.collapse(isStart);
                updateRangeProperties(this);
            };

            rangeProto.cloneRange = function() {
                return new WrappedRange(this.nativeRange.cloneRange());
            };

            rangeProto.refresh = function() {
                updateRangeProperties(this);
            };

            rangeProto.toString = function() {
                return this.nativeRange.toString();
            };

            // Create test range and node for feature detection

            var testTextNode = document.createTextNode("test");
            getBody(document).appendChild(testTextNode);
            var range = document.createRange();

            /*--------------------------------------------------------------------------------------------------------*/

            // Test for Firefox 2 bug that prevents moving the start of a Range to a point after its current end and
            // correct for it

            range.setStart(testTextNode, 0);
            range.setEnd(testTextNode, 0);

            try {
                range.setStart(testTextNode, 1);

                rangeProto.setStart = function(node, offset) {
                    this.nativeRange.setStart(node, offset);
                    updateRangeProperties(this);
                };

                rangeProto.setEnd = function(node, offset) {
                    this.nativeRange.setEnd(node, offset);
                    updateRangeProperties(this);
                };

                createBeforeAfterNodeSetter = function(name) {
                    return function(node) {
                        this.nativeRange[name](node);
                        updateRangeProperties(this);
                    };
                };

            } catch(ex) {

                rangeProto.setStart = function(node, offset) {
                    try {
                        this.nativeRange.setStart(node, offset);
                    } catch (ex) {
                        this.nativeRange.setEnd(node, offset);
                        this.nativeRange.setStart(node, offset);
                    }
                    updateRangeProperties(this);
                };

                rangeProto.setEnd = function(node, offset) {
                    try {
                        this.nativeRange.setEnd(node, offset);
                    } catch (ex) {
                        this.nativeRange.setStart(node, offset);
                        this.nativeRange.setEnd(node, offset);
                    }
                    updateRangeProperties(this);
                };

                createBeforeAfterNodeSetter = function(name, oppositeName) {
                    return function(node) {
                        try {
                            this.nativeRange[name](node);
                        } catch (ex) {
                            this.nativeRange[oppositeName](node);
                            this.nativeRange[name](node);
                        }
                        updateRangeProperties(this);
                    };
                };
            }

            rangeProto.setStartBefore = createBeforeAfterNodeSetter("setStartBefore", "setEndBefore");
            rangeProto.setStartAfter = createBeforeAfterNodeSetter("setStartAfter", "setEndAfter");
            rangeProto.setEndBefore = createBeforeAfterNodeSetter("setEndBefore", "setStartBefore");
            rangeProto.setEndAfter = createBeforeAfterNodeSetter("setEndAfter", "setStartAfter");

            /*--------------------------------------------------------------------------------------------------------*/

            // Always use DOM4-compliant selectNodeContents implementation: it's simpler and less code than testing
            // whether the native implementation can be trusted
            rangeProto.selectNodeContents = function(node) {
                this.setStartAndEnd(node, 0, dom.getNodeLength(node));
            };

            /*--------------------------------------------------------------------------------------------------------*/

            // Test for and correct WebKit bug that has the behaviour of compareBoundaryPoints round the wrong way for
            // constants START_TO_END and END_TO_START: https://bugs.webkit.org/show_bug.cgi?id=20738

            range.selectNodeContents(testTextNode);
            range.setEnd(testTextNode, 3);

            var range2 = document.createRange();
            range2.selectNodeContents(testTextNode);
            range2.setEnd(testTextNode, 4);
            range2.setStart(testTextNode, 2);

            if (range.compareBoundaryPoints(range.START_TO_END, range2) == -1 &&
                    range.compareBoundaryPoints(range.END_TO_START, range2) == 1) {
                // This is the wrong way round, so correct for it

                rangeProto.compareBoundaryPoints = function(type, range) {
                    range = range.nativeRange || range;
                    if (type == range.START_TO_END) {
                        type = range.END_TO_START;
                    } else if (type == range.END_TO_START) {
                        type = range.START_TO_END;
                    }
                    return this.nativeRange.compareBoundaryPoints(type, range);
                };
            } else {
                rangeProto.compareBoundaryPoints = function(type, range) {
                    return this.nativeRange.compareBoundaryPoints(type, range.nativeRange || range);
                };
            }

            /*--------------------------------------------------------------------------------------------------------*/

            // Test for IE 9 deleteContents() and extractContents() bug and correct it. See issue 107.

            var el = document.createElement("div");
            el.innerHTML = "123";
            var textNode = el.firstChild;
            var body = getBody(document);
            body.appendChild(el);

            range.setStart(textNode, 1);
            range.setEnd(textNode, 2);
            range.deleteContents();

            if (textNode.data == "13") {
                // Behaviour is correct per DOM4 Range so wrap the browser's implementation of deleteContents() and
                // extractContents()
                rangeProto.deleteContents = function() {
                    this.nativeRange.deleteContents();
                    updateRangeProperties(this);
                };

                rangeProto.extractContents = function() {
                    var frag = this.nativeRange.extractContents();
                    updateRangeProperties(this);
                    return frag;
                };
            } else {
            }

            body.removeChild(el);
            body = null;

            /*--------------------------------------------------------------------------------------------------------*/

            // Test for existence of createContextualFragment and delegate to it if it exists
            if (util.isHostMethod(range, "createContextualFragment")) {
                rangeProto.createContextualFragment = function(fragmentStr) {
                    return this.nativeRange.createContextualFragment(fragmentStr);
                };
            }

            /*--------------------------------------------------------------------------------------------------------*/

            // Clean up
            getBody(document).removeChild(testTextNode);
            range.detach();
            range2.detach();

            rangeProto.getName = function() {
                return "WrappedRange";
            };

            api.WrappedRange = WrappedRange;

            api.createNativeRange = function(doc) {
                doc = getContentDocument(doc, module, "createNativeRange");
                return doc.createRange();
            };
        })();
    }

    if (api.features.implementsTextRange) {
        /*
        This is a workaround for a bug where IE returns the wrong container element from the TextRange's parentElement()
        method. For example, in the following (where pipes denote the selection boundaries):

        <ul id="ul"><li id="a">| a </li><li id="b"> b |</li></ul>

        var range = document.selection.createRange();
        alert(range.parentElement().id); // Should alert "ul" but alerts "b"

        This method returns the common ancestor node of the following:
        - the parentElement() of the textRange
        - the parentElement() of the textRange after calling collapse(true)
        - the parentElement() of the textRange after calling collapse(false)
        */
        var getTextRangeContainerElement = function(textRange) {
            var parentEl = textRange.parentElement();
            var range = textRange.duplicate();
            range.collapse(true);
            var startEl = range.parentElement();
            range = textRange.duplicate();
            range.collapse(false);
            var endEl = range.parentElement();
            var startEndContainer = (startEl == endEl) ? startEl : dom.getCommonAncestor(startEl, endEl);

            return startEndContainer == parentEl ? startEndContainer : dom.getCommonAncestor(parentEl, startEndContainer);
        };

        var textRangeIsCollapsed = function(textRange) {
            return textRange.compareEndPoints("StartToEnd", textRange) == 0;
        };

        // Gets the boundary of a TextRange expressed as a node and an offset within that node. This function started out as
        // an improved version of code found in Tim Cameron Ryan's IERange (http://code.google.com/p/ierange/) but has
        // grown, fixing problems with line breaks in preformatted text, adding workaround for IE TextRange bugs, handling
        // for inputs and images, plus optimizations.
        var getTextRangeBoundaryPosition = function(textRange, wholeRangeContainerElement, isStart, isCollapsed, startInfo) {
            var workingRange = textRange.duplicate();
            workingRange.collapse(isStart);
            var containerElement = workingRange.parentElement();

            // Sometimes collapsing a TextRange that's at the start of a text node can move it into the previous node, so
            // check for that
            if (!dom.isOrIsAncestorOf(wholeRangeContainerElement, containerElement)) {
                containerElement = wholeRangeContainerElement;
            }


            // Deal with nodes that cannot "contain rich HTML markup". In practice, this means form inputs, images and
            // similar. See http://msdn.microsoft.com/en-us/library/aa703950%28VS.85%29.aspx
            if (!containerElement.canHaveHTML) {
                var pos = new DomPosition(containerElement.parentNode, dom.getNodeIndex(containerElement));
                return {
                    boundaryPosition: pos,
                    nodeInfo: {
                        nodeIndex: pos.offset,
                        containerElement: pos.node
                    }
                };
            }

            var workingNode = dom.getDocument(containerElement).createElement("span");

            // Workaround for HTML5 Shiv's insane violation of document.createElement(). See Rangy issue 104 and HTML5
            // Shiv issue 64: https://github.com/aFarkas/html5shiv/issues/64
            if (workingNode.parentNode) {
                workingNode.parentNode.removeChild(workingNode);
            }

            var comparison, workingComparisonType = isStart ? "StartToStart" : "StartToEnd";
            var previousNode, nextNode, boundaryPosition, boundaryNode;
            var start = (startInfo && startInfo.containerElement == containerElement) ? startInfo.nodeIndex : 0;
            var childNodeCount = containerElement.childNodes.length;
            var end = childNodeCount;

            // Check end first. Code within the loop assumes that the endth child node of the container is definitely
            // after the range boundary.
            var nodeIndex = end;

            while (true) {
                if (nodeIndex == childNodeCount) {
                    containerElement.appendChild(workingNode);
                } else {
                    containerElement.insertBefore(workingNode, containerElement.childNodes[nodeIndex]);
                }
                workingRange.moveToElementText(workingNode);
                comparison = workingRange.compareEndPoints(workingComparisonType, textRange);
                if (comparison == 0 || start == end) {
                    break;
                } else if (comparison == -1) {
                    if (end == start + 1) {
                        // We know the endth child node is after the range boundary, so we must be done.
                        break;
                    } else {
                        start = nodeIndex;
                    }
                } else {
                    end = (end == start + 1) ? start : nodeIndex;
                }
                nodeIndex = Math.floor((start + end) / 2);
                containerElement.removeChild(workingNode);
            }


            // We've now reached or gone past the boundary of the text range we're interested in
            // so have identified the node we want
            boundaryNode = workingNode.nextSibling;

            if (comparison == -1 && boundaryNode && isCharacterDataNode(boundaryNode)) {
                // This is a character data node (text, comment, cdata). The working range is collapsed at the start of the
                // node containing the text range's boundary, so we move the end of the working range to the boundary point
                // and measure the length of its text to get the boundary's offset within the node.
                workingRange.setEndPoint(isStart ? "EndToStart" : "EndToEnd", textRange);

                var offset;

                if (/[\r\n]/.test(boundaryNode.data)) {
                    /*
                    For the particular case of a boundary within a text node containing rendered line breaks (within a <pre>
                    element, for example), we need a slightly complicated approach to get the boundary's offset in IE. The
                    facts:

                    - Each line break is represented as \r in the text node's data/nodeValue properties
                    - Each line break is represented as \r\n in the TextRange's 'text' property
                    - The 'text' property of the TextRange does not contain trailing line breaks

                    To get round the problem presented by the final fact above, we can use the fact that TextRange's
                    moveStart() and moveEnd() methods return the actual number of characters moved, which is not necessarily
                    the same as the number of characters it was instructed to move. The simplest approach is to use this to
                    store the characters moved when moving both the start and end of the range to the start of the document
                    body and subtracting the start offset from the end offset (the "move-negative-gazillion" method).
                    However, this is extremely slow when the document is large and the range is near the end of it. Clearly
                    doing the mirror image (i.e. moving the range boundaries to the end of the document) has the same
                    problem.

                    Another approach that works is to use moveStart() to move the start boundary of the range up to the end
                    boundary one character at a time and incrementing a counter with the value returned by the moveStart()
                    call. However, the check for whether the start boundary has reached the end boundary is expensive, so
                    this method is slow (although unlike "move-negative-gazillion" is largely unaffected by the location of
                    the range within the document).

                    The method below is a hybrid of the two methods above. It uses the fact that a string containing the
                    TextRange's 'text' property with each \r\n converted to a single \r character cannot be longer than the
                    text of the TextRange, so the start of the range is moved that length initially and then a character at
                    a time to make up for any trailing line breaks not contained in the 'text' property. This has good
                    performance in most situations compared to the previous two methods.
                    */
                    var tempRange = workingRange.duplicate();
                    var rangeLength = tempRange.text.replace(/\r\n/g, "\r").length;

                    offset = tempRange.moveStart("character", rangeLength);
                    while ( (comparison = tempRange.compareEndPoints("StartToEnd", tempRange)) == -1) {
                        offset++;
                        tempRange.moveStart("character", 1);
                    }
                } else {
                    offset = workingRange.text.length;
                }
                boundaryPosition = new DomPosition(boundaryNode, offset);
            } else {

                // If the boundary immediately follows a character data node and this is the end boundary, we should favour
                // a position within that, and likewise for a start boundary preceding a character data node
                previousNode = (isCollapsed || !isStart) && workingNode.previousSibling;
                nextNode = (isCollapsed || isStart) && workingNode.nextSibling;
                if (nextNode && isCharacterDataNode(nextNode)) {
                    boundaryPosition = new DomPosition(nextNode, 0);
                } else if (previousNode && isCharacterDataNode(previousNode)) {
                    boundaryPosition = new DomPosition(previousNode, previousNode.data.length);
                } else {
                    boundaryPosition = new DomPosition(containerElement, dom.getNodeIndex(workingNode));
                }
            }

            // Clean up
            workingNode.parentNode.removeChild(workingNode);

            return {
                boundaryPosition: boundaryPosition,
                nodeInfo: {
                    nodeIndex: nodeIndex,
                    containerElement: containerElement
                }
            };
        };

        // Returns a TextRange representing the boundary of a TextRange expressed as a node and an offset within that node.
        // This function started out as an optimized version of code found in Tim Cameron Ryan's IERange
        // (http://code.google.com/p/ierange/)
        var createBoundaryTextRange = function(boundaryPosition, isStart) {
            var boundaryNode, boundaryParent, boundaryOffset = boundaryPosition.offset;
            var doc = dom.getDocument(boundaryPosition.node);
            var workingNode, childNodes, workingRange = getBody(doc).createTextRange();
            var nodeIsDataNode = isCharacterDataNode(boundaryPosition.node);

            if (nodeIsDataNode) {
                boundaryNode = boundaryPosition.node;
                boundaryParent = boundaryNode.parentNode;
            } else {
                childNodes = boundaryPosition.node.childNodes;
                boundaryNode = (boundaryOffset < childNodes.length) ? childNodes[boundaryOffset] : null;
                boundaryParent = boundaryPosition.node;
            }

            // Position the range immediately before the node containing the boundary
            workingNode = doc.createElement("span");

            // Making the working element non-empty element persuades IE to consider the TextRange boundary to be within the
            // element rather than immediately before or after it
            workingNode.innerHTML = "&#feff;";

            // insertBefore is supposed to work like appendChild if the second parameter is null. However, a bug report
            // for IERange suggests that it can crash the browser: http://code.google.com/p/ierange/issues/detail?id=12
            if (boundaryNode) {
                boundaryParent.insertBefore(workingNode, boundaryNode);
            } else {
                boundaryParent.appendChild(workingNode);
            }

            workingRange.moveToElementText(workingNode);
            workingRange.collapse(!isStart);

            // Clean up
            boundaryParent.removeChild(workingNode);

            // Move the working range to the text offset, if required
            if (nodeIsDataNode) {
                workingRange[isStart ? "moveStart" : "moveEnd"]("character", boundaryOffset);
            }

            return workingRange;
        };

        /*------------------------------------------------------------------------------------------------------------*/

        // This is a wrapper around a TextRange, providing full DOM Range functionality using rangy's DomRange as a
        // prototype

        WrappedTextRange = function(textRange) {
            this.textRange = textRange;
            this.refresh();
        };

        WrappedTextRange.prototype = new DomRange(document);

        WrappedTextRange.prototype.refresh = function() {
            var start, end, startBoundary;

            // TextRange's parentElement() method cannot be trusted. getTextRangeContainerElement() works around that.
            var rangeContainerElement = getTextRangeContainerElement(this.textRange);

            if (textRangeIsCollapsed(this.textRange)) {
                end = start = getTextRangeBoundaryPosition(this.textRange, rangeContainerElement, true,
                    true).boundaryPosition;
            } else {
                startBoundary = getTextRangeBoundaryPosition(this.textRange, rangeContainerElement, true, false);
                start = startBoundary.boundaryPosition;

                // An optimization used here is that if the start and end boundaries have the same parent element, the
                // search scope for the end boundary can be limited to exclude the portion of the element that precedes
                // the start boundary
                end = getTextRangeBoundaryPosition(this.textRange, rangeContainerElement, false, false,
                    startBoundary.nodeInfo).boundaryPosition;
            }

            this.setStart(start.node, start.offset);
            this.setEnd(end.node, end.offset);
        };

        WrappedTextRange.prototype.getName = function() {
            return "WrappedTextRange";
        };

        DomRange.copyComparisonConstants(WrappedTextRange);

        WrappedTextRange.rangeToTextRange = function(range) {
            if (range.collapsed) {
                return createBoundaryTextRange(new DomPosition(range.startContainer, range.startOffset), true);
            } else {
                var startRange = createBoundaryTextRange(new DomPosition(range.startContainer, range.startOffset), true);
                var endRange = createBoundaryTextRange(new DomPosition(range.endContainer, range.endOffset), false);
                var textRange = getBody( DomRange.getRangeDocument(range) ).createTextRange();
                textRange.setEndPoint("StartToStart", startRange);
                textRange.setEndPoint("EndToEnd", endRange);
                return textRange;
            }
        };

        api.WrappedTextRange = WrappedTextRange;

        // IE 9 and above have both implementations and Rangy makes both available. The next few lines sets which
        // implementation to use by default.
        if (!api.features.implementsDomRange || api.config.preferTextRange) {
            // Add WrappedTextRange as the Range property of the global object to allow expression like Range.END_TO_END to work
            var globalObj = (function() { return this; })();
            if (typeof globalObj.Range == "undefined") {
                globalObj.Range = WrappedTextRange;
            }

            api.createNativeRange = function(doc) {
                doc = getContentDocument(doc, module, "createNativeRange");
                return getBody(doc).createTextRange();
            };

            api.WrappedRange = WrappedTextRange;
        }
    }

    api.createRange = function(doc) {
        doc = getContentDocument(doc, module, "createRange");
        return new api.WrappedRange(api.createNativeRange(doc));
    };

    api.createRangyRange = function(doc) {
        doc = getContentDocument(doc, module, "createRangyRange");
        return new DomRange(doc);
    };

    api.createIframeRange = function(iframeEl) {
        module.deprecationNotice("createIframeRange()", "createRange(iframeEl)");
        return api.createRange(iframeEl);
    };

    api.createIframeRangyRange = function(iframeEl) {
        module.deprecationNotice("createIframeRangyRange()", "createRangyRange(iframeEl)");
        return api.createRangyRange(iframeEl);
    };

    api.addCreateMissingNativeApiListener(function(win) {
        var doc = win.document;
        if (typeof doc.createRange == "undefined") {
            doc.createRange = function() {
                return api.createRange(doc);
            };
        }
        doc = win = null;
    });
});
// This module creates a selection object wrapper that conforms as closely as possible to the Selection specification
// in the HTML Editing spec (http://dvcs.w3.org/hg/editing/raw-file/tip/editing.html#selections)
rangy.createCoreModule("WrappedSelection", ["DomRange", "WrappedRange"], function(api, module) {
    api.config.checkSelectionRanges = true;

    var BOOLEAN = "boolean";
    var NUMBER = "number";
    var dom = api.dom;
    var util = api.util;
    var isHostMethod = util.isHostMethod;
    var DomRange = api.DomRange;
    var WrappedRange = api.WrappedRange;
    var DOMException = api.DOMException;
    var DomPosition = dom.DomPosition;
    var getNativeSelection;
    var selectionIsCollapsed;
    var features = api.features;
    var CONTROL = "Control";
    var getDocument = dom.getDocument;
    var getBody = dom.getBody;
    var rangesEqual = DomRange.rangesEqual;


    // Utility function to support direction parameters in the API that may be a string ("backward" or "forward") or a
    // Boolean (true for backwards).
    function isDirectionBackward(dir) {
        return (typeof dir == "string") ? /^backward(s)?$/i.test(dir) : !!dir;
    }

    function getWindow(win, methodName) {
        if (!win) {
            return window;
        } else if (dom.isWindow(win)) {
            return win;
        } else if (win instanceof WrappedSelection) {
            return win.win;
        } else {
            var doc = dom.getContentDocument(win, module, methodName);
            return dom.getWindow(doc);
        }
    }

    function getWinSelection(winParam) {
        return getWindow(winParam, "getWinSelection").getSelection();
    }

    function getDocSelection(winParam) {
        return getWindow(winParam, "getDocSelection").document.selection;
    }

    function winSelectionIsBackward(sel) {
        var backward = false;
        if (sel.anchorNode) {
            backward = (dom.comparePoints(sel.anchorNode, sel.anchorOffset, sel.focusNode, sel.focusOffset) == 1);
        }
        return backward;
    }

    // Test for the Range/TextRange and Selection features required
    // Test for ability to retrieve selection
    var implementsWinGetSelection = isHostMethod(window, "getSelection"),
        implementsDocSelection = util.isHostObject(document, "selection");

    features.implementsWinGetSelection = implementsWinGetSelection;
    features.implementsDocSelection = implementsDocSelection;

    var useDocumentSelection = implementsDocSelection && (!implementsWinGetSelection || api.config.preferTextRange);

    if (useDocumentSelection) {
        getNativeSelection = getDocSelection;
        api.isSelectionValid = function(winParam) {
            var doc = getWindow(winParam, "isSelectionValid").document, nativeSel = doc.selection;

            // Check whether the selection TextRange is actually contained within the correct document
            return (nativeSel.type != "None" || getDocument(nativeSel.createRange().parentElement()) == doc);
        };
    } else if (implementsWinGetSelection) {
        getNativeSelection = getWinSelection;
        api.isSelectionValid = function() {
            return true;
        };
    } else {
        module.fail("Neither document.selection or window.getSelection() detected.");
    }

    api.getNativeSelection = getNativeSelection;

    var testSelection = getNativeSelection();
    var testRange = api.createNativeRange(document);
    var body = getBody(document);

    // Obtaining a range from a selection
    var selectionHasAnchorAndFocus = util.areHostProperties(testSelection,
        ["anchorNode", "focusNode", "anchorOffset", "focusOffset"]);

    features.selectionHasAnchorAndFocus = selectionHasAnchorAndFocus;

    // Test for existence of native selection extend() method
    var selectionHasExtend = isHostMethod(testSelection, "extend");
    features.selectionHasExtend = selectionHasExtend;

    // Test if rangeCount exists
    var selectionHasRangeCount = (typeof testSelection.rangeCount == NUMBER);
    features.selectionHasRangeCount = selectionHasRangeCount;

    var selectionSupportsMultipleRanges = false;
    var collapsedNonEditableSelectionsSupported = true;

    var addRangeBackwardToNative = selectionHasExtend ?
        function(nativeSelection, range) {
            var doc = DomRange.getRangeDocument(range);
            var endRange = api.createRange(doc);
            endRange.collapseToPoint(range.endContainer, range.endOffset);
            nativeSelection.addRange(getNativeRange(endRange));
            nativeSelection.extend(range.startContainer, range.startOffset);
        } : null;

    if (util.areHostMethods(testSelection, ["addRange", "getRangeAt", "removeAllRanges"]) &&
            typeof testSelection.rangeCount == NUMBER && features.implementsDomRange) {

        (function() {
            // Previously an iframe was used but this caused problems in some circumstances in IE, so tests are
            // performed on the current document's selection. See issue 109.

            // Note also that if a selection previously existed, it is wiped by these tests. This should usually be fine
            // because initialization usually happens when the document loads, but could be a problem for a script that
            // loads and initializes Rangy later. If anyone complains, code could be added to save and restore the
            // selection.
            var sel = window.getSelection();
            if (sel) {
                // Store the current selection
                var originalSelectionRangeCount = sel.rangeCount;
                var selectionHasMultipleRanges = (originalSelectionRangeCount > 1);
                var originalSelectionRanges = [];
                var originalSelectionBackward = winSelectionIsBackward(sel);
                for (var i = 0; i < originalSelectionRangeCount; ++i) {
                    originalSelectionRanges[i] = sel.getRangeAt(i);
                }

                // Create some test elements
                var body = getBody(document);
                var testEl = body.appendChild( document.createElement("div") );
                testEl.contentEditable = "false";
                var textNode = testEl.appendChild( document.createTextNode("\u00a0\u00a0\u00a0") );

                // Test whether the native selection will allow a collapsed selection within a non-editable element
				if (navigator.userAgent.toLowerCase().indexOf('chrome') != -1)
				{
					collapsedNonEditableSelectionsSupported = false;
					selectionSupportsMultipleRanges = false;
				}
				else
				{
					var r1 = document.createRange();
					r1.setStart(textNode, 1);
					r1.collapse(true);
					sel.addRange(r1);
					collapsedNonEditableSelectionsSupported = (sel.rangeCount == 1);
					sel.removeAllRanges();

					// Test whether the native selection is capable of supporting multiple ranges
					var r2 = r1.cloneRange();
					r1.setStart(textNode, 0);
					r2.setEnd(textNode, 3);
					r2.setStart(textNode, 2);
					sel.addRange(r1);
					sel.addRange(r2);
					selectionSupportsMultipleRanges = (sel.rangeCount == 2);
				}

                // Clean up
                body.removeChild(testEl);
                sel.removeAllRanges();

                for (i = 0; i < originalSelectionRangeCount; ++i) {
                    if (i == 0 && originalSelectionBackward) {
                        if (addRangeBackwardToNative) {
                            addRangeBackwardToNative(sel, originalSelectionRanges[i]);
                        } else {
                            api.warn("Rangy initialization: original selection was backwards but selection has been restored forwards because browser does not support Selection.extend");
                            sel.addRange(originalSelectionRanges[i])
                        }
                    } else {
                        sel.addRange(originalSelectionRanges[i])
                    }
                }
            }
        })();
    }

    features.selectionSupportsMultipleRanges = selectionSupportsMultipleRanges;
    features.collapsedNonEditableSelectionsSupported = collapsedNonEditableSelectionsSupported;

    // ControlRanges
    var implementsControlRange = false, testControlRange;

    if (body && isHostMethod(body, "createControlRange")) {
        testControlRange = body.createControlRange();
        if (util.areHostProperties(testControlRange, ["item", "add"])) {
            implementsControlRange = true;
        }
    }
    features.implementsControlRange = implementsControlRange;

    // Selection collapsedness
    if (selectionHasAnchorAndFocus) {
        selectionIsCollapsed = function(sel) {
            return sel.anchorNode === sel.focusNode && sel.anchorOffset === sel.focusOffset;
        };
    } else {
        selectionIsCollapsed = function(sel) {
            return sel.rangeCount ? sel.getRangeAt(sel.rangeCount - 1).collapsed : false;
        };
    }

    function updateAnchorAndFocusFromRange(sel, range, backward) {
        var anchorPrefix = backward ? "end" : "start", focusPrefix = backward ? "start" : "end";
        sel.anchorNode = range[anchorPrefix + "Container"];
        sel.anchorOffset = range[anchorPrefix + "Offset"];
        sel.focusNode = range[focusPrefix + "Container"];
        sel.focusOffset = range[focusPrefix + "Offset"];
    }

    function updateAnchorAndFocusFromNativeSelection(sel) {
        var nativeSel = sel.nativeSelection;
        sel.anchorNode = nativeSel.anchorNode;
        sel.anchorOffset = nativeSel.anchorOffset;
        sel.focusNode = nativeSel.focusNode;
        sel.focusOffset = nativeSel.focusOffset;
    }

    function updateEmptySelection(sel) {
        sel.anchorNode = sel.focusNode = null;
        sel.anchorOffset = sel.focusOffset = 0;
        sel.rangeCount = 0;
        sel.isCollapsed = true;
        sel._ranges.length = 0;
    }

    function getNativeRange(range) {
        var nativeRange;
        if (range instanceof DomRange) {
            nativeRange = api.createNativeRange(range.getDocument());
            nativeRange.setEnd(range.endContainer, range.endOffset);
            nativeRange.setStart(range.startContainer, range.startOffset);
        } else if (range instanceof WrappedRange) {
            nativeRange = range.nativeRange;
        } else if (features.implementsDomRange && (range instanceof dom.getWindow(range.startContainer).Range)) {
            nativeRange = range;
        }
        return nativeRange;
    }

    function rangeContainsSingleElement(rangeNodes) {
        if (!rangeNodes.length || rangeNodes[0].nodeType != 1) {
            return false;
        }
        for (var i = 1, len = rangeNodes.length; i < len; ++i) {
            if (!dom.isAncestorOf(rangeNodes[0], rangeNodes[i])) {
                return false;
            }
        }
        return true;
    }

    function getSingleElementFromRange(range) {
        var nodes = range.getNodes();
        if (!rangeContainsSingleElement(nodes)) {
            throw module.createError("getSingleElementFromRange: range " + range.inspect() + " did not consist of a single element");
        }
        return nodes[0];
    }

    // Simple, quick test which only needs to distinguish between a TextRange and a ControlRange
    function isTextRange(range) {
        return !!range && typeof range.text != "undefined";
    }

    function updateFromTextRange(sel, range) {
        // Create a Range from the selected TextRange
        var wrappedRange = new WrappedRange(range);
        sel._ranges = [wrappedRange];

        updateAnchorAndFocusFromRange(sel, wrappedRange, false);
        sel.rangeCount = 1;
        sel.isCollapsed = wrappedRange.collapsed;
    }

    function updateControlSelection(sel) {
        // Update the wrapped selection based on what's now in the native selection
        sel._ranges.length = 0;
        if (sel.docSelection.type == "None") {
            updateEmptySelection(sel);
        } else {
            var controlRange = sel.docSelection.createRange();
            if (isTextRange(controlRange)) {
                // This case (where the selection type is "Control" and calling createRange() on the selection returns
                // a TextRange) can happen in IE 9. It happens, for example, when all elements in the selected
                // ControlRange have been removed from the ControlRange and removed from the document.
                updateFromTextRange(sel, controlRange);
            } else {
                sel.rangeCount = controlRange.length;
                var range, doc = getDocument(controlRange.item(0));
                for (var i = 0; i < sel.rangeCount; ++i) {
                    range = api.createRange(doc);
                    range.selectNode(controlRange.item(i));
                    sel._ranges.push(range);
                }
                sel.isCollapsed = sel.rangeCount == 1 && sel._ranges[0].collapsed;
                updateAnchorAndFocusFromRange(sel, sel._ranges[sel.rangeCount - 1], false);
            }
        }
    }

    function addRangeToControlSelection(sel, range) {
        var controlRange = sel.docSelection.createRange();
        var rangeElement = getSingleElementFromRange(range);

        // Create a new ControlRange containing all the elements in the selected ControlRange plus the element
        // contained by the supplied range
        var doc = getDocument(controlRange.item(0));
        var newControlRange = getBody(doc).createControlRange();
        for (var i = 0, len = controlRange.length; i < len; ++i) {
            newControlRange.add(controlRange.item(i));
        }
        try {
            newControlRange.add(rangeElement);
        } catch (ex) {
            throw module.createError("addRange(): Element within the specified Range could not be added to control selection (does it have layout?)");
        }
        newControlRange.select();

        // Update the wrapped selection based on what's now in the native selection
        updateControlSelection(sel);
    }

    var getSelectionRangeAt;

    if (isHostMethod(testSelection, "getRangeAt")) {
        // try/catch is present because getRangeAt() must have thrown an error in some browser and some situation.
        // Unfortunately, I didn't write a comment about the specifics and am now scared to take it out. Let that be a
        // lesson to us all, especially me.
        getSelectionRangeAt = function(sel, index) {
            try {
                return sel.getRangeAt(index);
            } catch (ex) {
                return null;
            }
        };
    } else if (selectionHasAnchorAndFocus) {
        getSelectionRangeAt = function(sel) {
            var doc = getDocument(sel.anchorNode);
            var range = api.createRange(doc);
            range.setStartAndEnd(sel.anchorNode, sel.anchorOffset, sel.focusNode, sel.focusOffset);

            // Handle the case when the selection was selected backwards (from the end to the start in the
            // document)
            if (range.collapsed !== this.isCollapsed) {
                range.setStartAndEnd(sel.focusNode, sel.focusOffset, sel.anchorNode, sel.anchorOffset);
            }

            return range;
        };
    }

    function WrappedSelection(selection, docSelection, win) {
        this.nativeSelection = selection;
        this.docSelection = docSelection;
        this._ranges = [];
        this.win = win;
        this.refresh();
    }

    WrappedSelection.prototype = api.selectionPrototype;

    function deleteProperties(sel) {
        sel.win = sel.anchorNode = sel.focusNode = sel._ranges = null;
        sel.rangeCount = sel.anchorOffset = sel.focusOffset = 0;
        sel.detached = true;
    }

    var cachedRangySelections = [];

    function actOnCachedSelection(win, action) {
        var i = cachedRangySelections.length, cached, sel;
        while (i--) {
            cached = cachedRangySelections[i];
            sel = cached.selection;
            if (action == "deleteAll") {
                deleteProperties(sel);
            } else if (cached.win == win) {
                if (action == "delete") {
                    cachedRangySelections.splice(i, 1);
                    return true;
                } else {
                    return sel;
                }
            }
        }
        if (action == "deleteAll") {
            cachedRangySelections.length = 0;
        }
        return null;
    }

    var getSelection = function(win) {
        // Check if the parameter is a Rangy Selection object
        if (win && win instanceof WrappedSelection) {
            win.refresh();
            return win;
        }

        win = getWindow(win, "getNativeSelection");

        var sel = actOnCachedSelection(win);
        var nativeSel = getNativeSelection(win), docSel = implementsDocSelection ? getDocSelection(win) : null;
        if (sel) {
            sel.nativeSelection = nativeSel;
            sel.docSelection = docSel;
            sel.refresh();
        } else {
            sel = new WrappedSelection(nativeSel, docSel, win);
            cachedRangySelections.push( { win: win, selection: sel } );
        }
        return sel;
    };

    api.getSelection = getSelection;

    api.getIframeSelection = function(iframeEl) {
        module.deprecationNotice("getIframeSelection()", "getSelection(iframeEl)");
        return api.getSelection(dom.getIframeWindow(iframeEl));
    };

    var selProto = WrappedSelection.prototype;

    function createControlSelection(sel, ranges) {
        // Ensure that the selection becomes of type "Control"
        var doc = getDocument(ranges[0].startContainer);
        var controlRange = getBody(doc).createControlRange();
        for (var i = 0, el, len = ranges.length; i < len; ++i) {
            el = getSingleElementFromRange(ranges[i]);
            try {
                controlRange.add(el);
            } catch (ex) {
                throw module.createError("setRanges(): Element within one of the specified Ranges could not be added to control selection (does it have layout?)");
            }
        }
        controlRange.select();

        // Update the wrapped selection based on what's now in the native selection
        updateControlSelection(sel);
    }

    // Selecting a range
    if (!useDocumentSelection && selectionHasAnchorAndFocus && util.areHostMethods(testSelection, ["removeAllRanges", "addRange"])) {
        selProto.removeAllRanges = function() {
            this.nativeSelection.removeAllRanges();
            updateEmptySelection(this);
        };

        var addRangeBackward = function(sel, range) {
            addRangeBackwardToNative(sel.nativeSelection, range);
            sel.refresh();
        };

        if (selectionHasRangeCount) {
            selProto.addRange = function(range, direction) {
                if (implementsControlRange && implementsDocSelection && this.docSelection.type == CONTROL) {
                    addRangeToControlSelection(this, range);
                } else {
                    if (isDirectionBackward(direction) && selectionHasExtend) {
                        addRangeBackward(this, range);
                    } else {
                        var previousRangeCount;
                        if (selectionSupportsMultipleRanges) {
                            previousRangeCount = this.rangeCount;
                        } else {
                            this.removeAllRanges();
                            previousRangeCount = 0;
                        }
                        // Clone the native range so that changing the selected range does not affect the selection.
                        // This is contrary to the spec but is the only way to achieve consistency between browsers. See
                        // issue 80.
                        this.nativeSelection.addRange(getNativeRange(range).cloneRange());

                        // Check whether adding the range was successful
                        this.rangeCount = this.nativeSelection.rangeCount;

                        if (this.rangeCount == previousRangeCount + 1) {
                            // The range was added successfully

                            // Check whether the range that we added to the selection is reflected in the last range extracted from
                            // the selection
                            if (api.config.checkSelectionRanges) {
                                var nativeRange = getSelectionRangeAt(this.nativeSelection, this.rangeCount - 1);
                                if (nativeRange && !rangesEqual(nativeRange, range)) {
                                    // Happens in WebKit with, for example, a selection placed at the start of a text node
                                    range = new WrappedRange(nativeRange);
                                }
                            }
                            this._ranges[this.rangeCount - 1] = range;
                            updateAnchorAndFocusFromRange(this, range, selectionIsBackward(this.nativeSelection));
                            this.isCollapsed = selectionIsCollapsed(this);
                        } else {
                            // The range was not added successfully. The simplest thing is to refresh
                            this.refresh();
                        }
                    }
                }
            };
        } else {
            selProto.addRange = function(range, direction) {
                if (isDirectionBackward(direction) && selectionHasExtend) {
                    addRangeBackward(this, range);
                } else {
                    this.nativeSelection.addRange(getNativeRange(range));
                    this.refresh();
                }
            };
        }

        selProto.setRanges = function(ranges) {
            if (implementsControlRange && ranges.length > 1) {
                createControlSelection(this, ranges);
            } else {
                this.removeAllRanges();
                for (var i = 0, len = ranges.length; i < len; ++i) {
                    this.addRange(ranges[i]);
                }
            }
        };
    } else if (isHostMethod(testSelection, "empty") && isHostMethod(testRange, "select") &&
               implementsControlRange && useDocumentSelection) {

        selProto.removeAllRanges = function() {
            // Added try/catch as fix for issue #21
            try {
                this.docSelection.empty();

                // Check for empty() not working (issue #24)
                if (this.docSelection.type != "None") {
                    // Work around failure to empty a control selection by instead selecting a TextRange and then
                    // calling empty()
                    var doc;
                    if (this.anchorNode) {
                        doc = getDocument(this.anchorNode);
                    } else if (this.docSelection.type == CONTROL) {
                        var controlRange = this.docSelection.createRange();
                        if (controlRange.length) {
                            doc = getDocument( controlRange.item(0) );
                        }
                    }
                    if (doc) {
                        var textRange = getBody(doc).createTextRange();
                        textRange.select();
                        this.docSelection.empty();
                    }
                }
            } catch(ex) {}
            updateEmptySelection(this);
        };

        selProto.addRange = function(range) {
            if (this.docSelection.type == CONTROL) {
                addRangeToControlSelection(this, range);
            } else {
                api.WrappedTextRange.rangeToTextRange(range).select();
                this._ranges[0] = range;
                this.rangeCount = 1;
                this.isCollapsed = this._ranges[0].collapsed;
                updateAnchorAndFocusFromRange(this, range, false);
            }
        };

        selProto.setRanges = function(ranges) {
            this.removeAllRanges();
            var rangeCount = ranges.length;
            if (rangeCount > 1) {
                createControlSelection(this, ranges);
            } else if (rangeCount) {
                this.addRange(ranges[0]);
            }
        };
    } else {
        module.fail("No means of selecting a Range or TextRange was found");
        return false;
    }

    selProto.getRangeAt = function(index) {
        if (index < 0 || index >= this.rangeCount) {
            throw new DOMException("INDEX_SIZE_ERR");
        } else {
            // Clone the range to preserve selection-range independence. See issue 80.
            return this._ranges[index].cloneRange();
        }
    };

    var refreshSelection;

    if (useDocumentSelection) {
        refreshSelection = function(sel) {
            var range;
            if (api.isSelectionValid(sel.win)) {
                range = sel.docSelection.createRange();
            } else {
                range = getBody(sel.win.document).createTextRange();
                range.collapse(true);
            }

            if (sel.docSelection.type == CONTROL) {
                updateControlSelection(sel);
            } else if (isTextRange(range)) {
                updateFromTextRange(sel, range);
            } else {
                updateEmptySelection(sel);
            }
        };
    } else if (isHostMethod(testSelection, "getRangeAt") && typeof testSelection.rangeCount == NUMBER) {
        refreshSelection = function(sel) {
            if (implementsControlRange && implementsDocSelection && sel.docSelection.type == CONTROL) {
                updateControlSelection(sel);
            } else {
                sel._ranges.length = sel.rangeCount = sel.nativeSelection.rangeCount;
                if (sel.rangeCount) {
                    for (var i = 0, len = sel.rangeCount; i < len; ++i) {
                        sel._ranges[i] = new api.WrappedRange(sel.nativeSelection.getRangeAt(i));
                    }
                    updateAnchorAndFocusFromRange(sel, sel._ranges[sel.rangeCount - 1], selectionIsBackward(sel.nativeSelection));
                    sel.isCollapsed = selectionIsCollapsed(sel);
                } else {
                    updateEmptySelection(sel);
                }
            }
        };
    } else if (selectionHasAnchorAndFocus && typeof testSelection.isCollapsed == BOOLEAN && typeof testRange.collapsed == BOOLEAN && features.implementsDomRange) {
        refreshSelection = function(sel) {
            var range, nativeSel = sel.nativeSelection;
            if (nativeSel.anchorNode) {
                range = getSelectionRangeAt(nativeSel, 0);
                sel._ranges = [range];
                sel.rangeCount = 1;
                updateAnchorAndFocusFromNativeSelection(sel);
                sel.isCollapsed = selectionIsCollapsed(sel);
            } else {
                updateEmptySelection(sel);
            }
        };
    } else {
        module.fail("No means of obtaining a Range or TextRange from the user's selection was found");
        return false;
    }

    selProto.refresh = function(checkForChanges) {
        var oldRanges = checkForChanges ? this._ranges.slice(0) : null;
        var oldAnchorNode = this.anchorNode, oldAnchorOffset = this.anchorOffset;

        refreshSelection(this);
        if (checkForChanges) {
            // Check the range count first
            var i = oldRanges.length;
            if (i != this._ranges.length) {
                return true;
            }

            // Now check the direction. Checking the anchor position is the same is enough since we're checking all the
            // ranges after this
            if (this.anchorNode != oldAnchorNode || this.anchorOffset != oldAnchorOffset) {
                return true;
            }

            // Finally, compare each range in turn
            while (i--) {
                if (!rangesEqual(oldRanges[i], this._ranges[i])) {
                    return true;
                }
            }
            return false;
        }
    };

    // Removal of a single range
    var removeRangeManually = function(sel, range) {
        var ranges = sel.getAllRanges();
        sel.removeAllRanges();
        for (var i = 0, len = ranges.length; i < len; ++i) {
            if (!rangesEqual(range, ranges[i])) {
                sel.addRange(ranges[i]);
            }
        }
        if (!sel.rangeCount) {
            updateEmptySelection(sel);
        }
    };

    if (implementsControlRange) {
        selProto.removeRange = function(range) {
            if (this.docSelection.type == CONTROL) {
                var controlRange = this.docSelection.createRange();
                var rangeElement = getSingleElementFromRange(range);

                // Create a new ControlRange containing all the elements in the selected ControlRange minus the
                // element contained by the supplied range
                var doc = getDocument(controlRange.item(0));
                var newControlRange = getBody(doc).createControlRange();
                var el, removed = false;
                for (var i = 0, len = controlRange.length; i < len; ++i) {
                    el = controlRange.item(i);
                    if (el !== rangeElement || removed) {
                        newControlRange.add(controlRange.item(i));
                    } else {
                        removed = true;
                    }
                }
                newControlRange.select();

                // Update the wrapped selection based on what's now in the native selection
                updateControlSelection(this);
            } else {
                removeRangeManually(this, range);
            }
        };
    } else {
        selProto.removeRange = function(range) {
            removeRangeManually(this, range);
        };
    }

    // Detecting if a selection is backward
    var selectionIsBackward;
    if (!useDocumentSelection && selectionHasAnchorAndFocus && features.implementsDomRange) {
        selectionIsBackward = winSelectionIsBackward;

        selProto.isBackward = function() {
            return selectionIsBackward(this);
        };
    } else {
        selectionIsBackward = selProto.isBackward = function() {
            return false;
        };
    }

    // Create an alias for backwards compatibility. From 1.3, everything is "backward" rather than "backwards"
    selProto.isBackwards = selProto.isBackward;

    // Selection stringifier
    // This is conformant to the old HTML5 selections draft spec but differs from WebKit and Mozilla's implementation.
    // The current spec does not yet define this method.
    selProto.toString = function() {
        var rangeTexts = [];
        for (var i = 0, len = this.rangeCount; i < len; ++i) {
            rangeTexts[i] = "" + this._ranges[i];
        }
        return rangeTexts.join("");
    };

    function assertNodeInSameDocument(sel, node) {
        if (sel.win.document != getDocument(node)) {
            throw new DOMException("WRONG_DOCUMENT_ERR");
        }
    }

    // No current browser conforms fully to the spec for this method, so Rangy's own method is always used
    selProto.collapse = function(node, offset) {
        assertNodeInSameDocument(this, node);
        var range = api.createRange(node);
        range.collapseToPoint(node, offset);
        this.setSingleRange(range);
        this.isCollapsed = true;
    };

    selProto.collapseToStart = function() {
        if (this.rangeCount) {
            var range = this._ranges[0];
            this.collapse(range.startContainer, range.startOffset);
        } else {
            throw new DOMException("INVALID_STATE_ERR");
        }
    };

    selProto.collapseToEnd = function() {
        if (this.rangeCount) {
            var range = this._ranges[this.rangeCount - 1];
            this.collapse(range.endContainer, range.endOffset);
        } else {
            throw new DOMException("INVALID_STATE_ERR");
        }
    };

    // The spec is very specific on how selectAllChildren should be implemented so the native implementation is
    // never used by Rangy.
    selProto.selectAllChildren = function(node) {
        assertNodeInSameDocument(this, node);
        var range = api.createRange(node);
        range.selectNodeContents(node);
        this.setSingleRange(range);
    };

    selProto.deleteFromDocument = function() {
        // Sepcial behaviour required for IE's control selections
        if (implementsControlRange && implementsDocSelection && this.docSelection.type == CONTROL) {
            var controlRange = this.docSelection.createRange();
            var element;
            while (controlRange.length) {
                element = controlRange.item(0);
                controlRange.remove(element);
                element.parentNode.removeChild(element);
            }
            this.refresh();
        } else if (this.rangeCount) {
            var ranges = this.getAllRanges();
            if (ranges.length) {
                this.removeAllRanges();
                for (var i = 0, len = ranges.length; i < len; ++i) {
                    ranges[i].deleteContents();
                }
                // The spec says nothing about what the selection should contain after calling deleteContents on each
                // range. Firefox moves the selection to where the final selected range was, so we emulate that
                this.addRange(ranges[len - 1]);
            }
        }
    };

    // The following are non-standard extensions
    selProto.eachRange = function(func, returnValue) {
        for (var i = 0, len = this._ranges.length; i < len; ++i) {
            if ( func( this.getRangeAt(i) ) ) {
                return returnValue;
            }
        }
    };

    selProto.getAllRanges = function() {
        var ranges = [];
        this.eachRange(function(range) {
            ranges.push(range);
        });
        return ranges;
    };

    selProto.setSingleRange = function(range, direction) {
        this.removeAllRanges();
        this.addRange(range, direction);
    };

    selProto.callMethodOnEachRange = function(methodName, params) {
        var results = [];
        this.eachRange( function(range) {
            results.push( range[methodName].apply(range, params) );
        } );
        return results;
    };

    function createStartOrEndSetter(isStart) {
        return function(node, offset) {
            var range;
            if (this.rangeCount) {
                range = this.getRangeAt(0);
                range["set" + (isStart ? "Start" : "End")](node, offset);
            } else {
                range = api.createRange(this.win.document);
                range.setStartAndEnd(node, offset);
            }
            this.setSingleRange(range, this.isBackward());
        };
    }

    selProto.setStart = createStartOrEndSetter(true);
    selProto.setEnd = createStartOrEndSetter(false);

    // Add select() method to Range prototype. Any existing selection will be removed.
    api.rangePrototype.select = function(direction) {
        getSelection( this.getDocument() ).setSingleRange(this, direction);
    };

    selProto.changeEachRange = function(func) {
        var ranges = [];
        var backward = this.isBackward();

        this.eachRange(function(range) {
            func(range);
            ranges.push(range);
        });

        this.removeAllRanges();
        if (backward && ranges.length == 1) {
            this.addRange(ranges[0], "backward");
        } else {
            this.setRanges(ranges);
        }
    };

    selProto.containsNode = function(node, allowPartial) {
        return this.eachRange( function(range) {
            return range.containsNode(node, allowPartial);
        }, true );
    };

    selProto.getBookmark = function(containerNode) {
        return {
            backward: this.isBackward(),
            rangeBookmarks: this.callMethodOnEachRange("getBookmark", [containerNode])
        };
    };

    selProto.moveToBookmark = function(bookmark) {
        var selRanges = [];
        for (var i = 0, rangeBookmark, range; rangeBookmark = bookmark.rangeBookmarks[i++]; ) {
            range = api.createRange(this.win);
            range.moveToBookmark(rangeBookmark);
            selRanges.push(range);
        }
        if (bookmark.backward) {
            this.setSingleRange(selRanges[0], "backward");
        } else {
            this.setRanges(selRanges);
        }
    };

    selProto.toHtml = function() {
        return this.callMethodOnEachRange("toHtml").join("");
    };

    function inspect(sel) {
        var rangeInspects = [];
        var anchor = new DomPosition(sel.anchorNode, sel.anchorOffset);
        var focus = new DomPosition(sel.focusNode, sel.focusOffset);
        var name = (typeof sel.getName == "function") ? sel.getName() : "Selection";

        if (typeof sel.rangeCount != "undefined") {
            for (var i = 0, len = sel.rangeCount; i < len; ++i) {
                rangeInspects[i] = DomRange.inspect(sel.getRangeAt(i));
            }
        }
        return "[" + name + "(Ranges: " + rangeInspects.join(", ") +
                ")(anchor: " + anchor.inspect() + ", focus: " + focus.inspect() + "]";
    }

    selProto.getName = function() {
        return "WrappedSelection";
    };

    selProto.inspect = function() {
        return inspect(this);
    };

    selProto.detach = function() {
        actOnCachedSelection(this.win, "delete");
        deleteProperties(this);
    };

    WrappedSelection.detachAll = function() {
        actOnCachedSelection(null, "deleteAll");
    };

    WrappedSelection.inspect = inspect;
    WrappedSelection.isDirectionBackward = isDirectionBackward;

    api.Selection = WrappedSelection;

    api.selectionPrototype = selProto;

    api.addCreateMissingNativeApiListener(function(win) {
        if (typeof win.getSelection == "undefined") {
            win.getSelection = function() {
                return getSelection(win);
            };
        }
        win = null;
    });
});

/* End */
;
; /* Start:"a:4:{s:4:"full";s:66:"/bitrix/js/fileman/html_editor/html-actions.min.js?145227744854400";s:6:"source";s:46:"/bitrix/js/fileman/html_editor/html-actions.js";s:3:"min";s:50:"/bitrix/js/fileman/html_editor/html-actions.min.js";s:3:"map";s:50:"/bitrix/js/fileman/html_editor/html-actions.map.js";}"*/
(function(){function e(e){this.editor=e;this.document=e.sandbox.GetDocument();BX.addCustomEvent(this.editor,"OnIframeReInit",BX.proxy(function(){this.document=this.editor.sandbox.GetDocument()},this));this.actions=this.GetActionList();this.contentActionIndex={removeFormat:1,bold:1,italic:1,underline:1,strikeout:1,fontSize:1,foreColor:1,backgroundColor:1,formatInline:1,formatBlock:1,createLink:1,insertHTML:1,insertImage:1,insertLineBreak:1,removeLink:1,insertOrderedList:1,insertUnorderedList:1,align:1,indent:1,outdent:1,formatStyle:1,fontFamily:1,universalFormatStyle:1,quote:1,code:1,sub:1,sup:1,insertSmile:1}}e.prototype={IsSupportedByBrowser:function(e){var t=BX.browser.IsIE()||BX.browser.IsIE10()||BX.browser.IsIE11(),n={indent:t,outdent:t,formatBlock:t,insertUnorderedList:BX.browser.IsIE()||BX.browser.IsOpera(),insertOrderedList:BX.browser.IsIE()||BX.browser.IsOpera()},r={insertHTML:BX.browser.IsFirefox()};if(!n[e]){try{return this.document.queryCommandSupported(e)}catch(i){}try{return this.document.queryCommandEnabled(e)}catch(o){return!!r[e]}}return false},IsSupported:function(e){return!!this.actions[e]},IsContentAction:function(e){return this.contentActionIndex[e]},Exec:function(e,t,n){var r=this,i=this.actions[e],o=i&&i.exec,a=this.IsContentAction(e),l=null;if(!n){this.editor.On("OnBeforeCommandExec",[a,e,i])}if(a){this.editor.Focus(false)}if(o){l=o.apply(i,arguments)}else{try{l=this.document.execCommand(e,false,t)}catch(s){}}if(a){setTimeout(function(){r.editor.Focus(false)},1)}if(!n){this.editor.On("OnAfterCommandExec",[a,e])}return l},CheckState:function(e,t){var n=this.actions[e],r=null;if(n&&n.state){r=n.state.apply(n,arguments)}else{try{r=this.document.queryCommandState(e)}catch(i){r=false}}return r},GetValue:function(e){var t=this.commands[e],n=t&&t.value;if(n){return n.call(t,this.composer,e)}else{try{return this.document.queryCommandValue(e)}catch(r){return null}}},GetActionList:function(){this.actions={changeView:this.GetChangeView(),splitMode:this.GetChangeSplitMode(),fullscreen:this.GetFullscreen(),changeTemplate:this.GetChangeTemplate(),removeFormat:this.GetRemoveFormat(),bold:this.GetBold(),italic:this.GetItalic(),underline:this.GetUnderline(),strikeout:this.GetStrikeout(),fontSize:this.GetFontSize(),foreColor:this.GetForeColor(),backgroundColor:this.GetBackgroundColor(),formatInline:this.GetFormatInline(),formatBlock:this.GetFormatBlock(),createLink:this.GetCreateLink(),insertHTML:this.GetInsertHTML(),insertImage:this.GetInsertImage(),insertLineBreak:this.GetInsertLineBreak(),insertTable:this.GetInsertTable(),removeLink:this.GetRemoveLink(),insertOrderedList:this.GetInsertList({bOrdered:true}),insertUnorderedList:this.GetInsertList({bOrdered:false}),align:this.GetAlign(),indent:this.GetIndent(),outdent:this.GetOutdent(),formatStyle:this.GetFormatStyle(),fontFamily:this.GetFontFamily(),universalFormatStyle:this.GetUniversalFormatStyle(),selectNode:this.GetSelectNode(),doUndo:this.GetUndoRedo(true),doRedo:this.GetUndoRedo(false),sub:this.GetSubSup("sub"),sup:this.GetSubSup("sup"),quote:this.GetQuote(),code:this.GetCode(),insertSmile:this.GetInsertSmile(),tableOperation:this.GetTableOperation(),formatBbCode:this.GetFormatBbCode()};this.editor.On("OnGetActionsList");return this.actions},GetChangeView:function(){var e=this;return{exec:function(){var t=arguments[1];if({code:1,wysiwyg:1,split:1}[t])e.editor.SetView(t)},state:function(){return false},value:function(){}}},GetChangeSplitMode:function(){var e=this;return{exec:function(){e.editor.SetSplitMode(arguments[1]==1)},state:function(){return e.editor.GetSplitMode()},value:function(){}}},GetFullscreen:function(){var e=this;return{exec:function(){e.editor.Expand()},state:function(){return e.editor.IsExpanded()},value:function(){}}},GetFormatInline:function(){var e=this,t={strong:"b",em:"i",b:"strong",i:"em"},n={};function r(e,t,n){var r=e+":";if(n)r+=n;if(t){for(var i in t)if(t.hasOwnProperty(i))r+=i+"="+t[i]+";"}return r}function i(i,o,a){var l=r(i,o,a);if(!n[l]){var s=t[i];var d=s?[i.toLowerCase(),s.toLowerCase()]:[i.toLowerCase()];n[l]=new e.editor.HTMLStyler(e.editor,d,o,a,true)}return n[l]}return{exec:function(t,n,r,o,a,l){l=!l||typeof l!="object"?{}:l;e.editor.iframeView.Focus();var s=e.editor.selection.GetRange();if(!s){return false}var d=i(r,o,a);if(l.bClear){s=d.UndoToRange(s,false)}else{s=d.ToggleRange(s)}setTimeout(function(){e.editor.selection.SetSelection(s);var t=e.editor.selection.GetSelectedNode();if(t&&t.nodeType==1){e.editor.lastCreatedId=Math.round(Math.random()*1e6);t.setAttribute("data-bx-last-created-id",e.editor.lastCreatedId)}},10)},state:function(n,r,o,a,l){var s=e.editor.GetIframeDoc(),d=t[o]||o;if(!e.editor.util.DocumentHasTag(s,o)&&(o!=d&&!e.editor.util.DocumentHasTag(s,d))){return false}var f=e.editor.selection.GetRange();if(!f){return false}var c=i(o,a,l);return c.IsAppliedToRange(f,false)},value:BX.DoNothing}},GetFormatBlock:function(){var e=this,t="DIV",n=e.editor.GetBlockTags(),r=e.editor.NESTED_BLOCK_TAGS;function i(e,t,n){if(e.className){o(e,n);e.className+=" "+t}else{e.className=t}}function o(e,t){e.className=e.className.replace(t,"")}function a(t){var n=e.editor.util.GetNextNotEmptySibling(t),r=e.editor.util.GetPreviousNotEmptySibling(t);if(n&&!d(n)){t.parentNode.insertBefore(e.document.createElement("BR"),n)}if(r&&!d(r)){t.parentNode.insertBefore(e.document.createElement("BR"),t)}}function l(t){var n=e.editor.util.GetNextNotEmptySibling(t),r=e.editor.util.GetPreviousNotEmptySibling(t);if(n&&n.nodeName==="BR"){n.parentNode.removeChild(n)}if(r&&r.nodeName==="BR"){r.parentNode.removeChild(r)}}function s(e){var t=e.lastChild;if(t&&t.nodeName==="BR"){t.parentNode.removeChild(t)}}function d(t){return t.nodeName==="BR"||e.editor.util.IsBlockElement(t)}function f(t,n,r,i){if(r||i){}e.document.execCommand(t,false,n)}function c(e){return!e.className||BX.util.trim(e.className)===""}function u(t,n,r,i){if(n){if(t.nodeName!==n){t=e.editor.util.RenameNode(t,n)}if(r){t.className=r}if(i&&i.length>0){e.editor.util.SetCss(t,i)}}else{a(t);e.editor.util.ReplaceWithOwnChildren(t)}}return{exec:function(i,o,d,f,c){c=c||{};o=typeof o==="string"?o.toUpperCase():o;var m,h,g,N,p=c.range||e.editor.selection.GetRange(),v=o?e.actions.formatBlock.state(i,o,d,f):false,B;if(c.range)e.editor.selection.RestoreBookmark();if(v){if(BX.util.in_array(o,r)&&c.nestedBlocks!==false){v=e.document.createElement(o||t);if(d){v.className=d}e.editor.selection.Surround(v);e.editor.selection.SelectNode(v)}else{e.editor.util.SetCss(v,f);if(d){v.className=d;if(BX.browser.IsFirefox()){var b="bx-editor-temp-"+Math.round(Math.random()*1e6);v.id=b;v.parentNode.innerHTML=v.parentNode.innerHTML;v=e.editor.GetIframeElement(b);if(v)v.removeAttribute("id")}}setTimeout(function(){if(v){e.editor.selection.SelectNode(v)}},10)}}else{if(o===null||BX.util.in_array(o,n)){v=false;B=e.editor.selection.GetSelectedNode();if(B){if(B.nodeType==1&&BX.util.in_array(B.nodeName,n)){v=B}else{v=BX.findParent(B,function(e){return BX.util.in_array(e.nodeName,n)},e.document.body)}}else{var C=e.editor.selection.GetCommonAncestorForRange(p);if(C&&C.nodeName!=="BODY"&&BX.util.in_array(C.nodeName,n)){v=C}}if(v&&!e.actions.quote.checkNode(v)){e.editor.selection.ExecuteAndRestoreSimple(function(){u(v,o,d,f)});return true}}v=BX.create(o||t,{},e.document);if(d){v.className=d}e.editor.util.SetCss(v,f);if(p.collapsed||B&&B.nodeType==3){e.editor.selection.SelectNode(B)}else if(p.collapsed){e.editor.selection.SelectLine()}e.editor.selection.Surround(v,p);l(v);s(v);if(c.leaveChilds){return v}if(o&&!BX.util.in_array(o,r)){p=e.editor.selection.GetRange();N=p.getNodes([1]);if(o=="P"){m=v.getElementsByTagName(o);for(h=0;h<N.length;h++){if(f&&e.editor.util.CheckCss(N[h],f,false)&&e.editor.util.IsEmptyNode(N[h],true,true)){BX.remove(N[h])}}while(m[0]){a(m[0]);e.editor.util.ReplaceWithOwnChildren(m[0])}}else if(o.substr(0,1)=="H"){for(h=0;h<N.length;h++){if(N[h].nodeName!==o&&e.editor.util.IsEmptyNode(N[h],true,true)){BX.remove(N[h])}}var x=BX.findChild(v,function(e){return BX.util.in_array(e.nodeName,n)&&e.nodeName.substr(0,1)=="H"},true,true);for(h=0;h<x.length;h++){a(x[h]);e.editor.util.ReplaceWithOwnChildren(x[h])}}}if(v&&c.bxTagParams&&typeof c.bxTagParams=="object"){e.editor.SetBxTag(v,c.bxTagParams)}if(v&&v.parentNode){var I=v.parentNode;if(I.nodeName=="UL"||I.nodeName=="OL"){var S=e.editor.util.GetPreviousNotEmptySibling(v);if(e.editor.util.IsEmptyLi(S)){BX.remove(S)}S=e.editor.util.GetNextNotEmptySibling(v);if(e.editor.util.IsEmptyLi(S)){BX.remove(S)}if(!e.editor.util.GetPreviousNotEmptySibling(v)&&!e.editor.util.GetNextNotEmptySibling(v)){var y=v.cloneNode(false);I.parentNode.insertBefore(y,I);e.editor.util.ReplaceWithOwnChildren(v);y.appendChild(I)}}if(v.firstChild&&v.firstChild.nodeName=="BLOCKQUOTE"){var T=e.editor.util.GetPreviousNotEmptySibling(v);if(T&&T.nodeName=="BLOCKQUOTE"&&e.editor.util.IsEmptyNode(T)){BX.remove(T)}}if((v.nodeName=="BLOCKQUOTE"||v.nodeName=="PRE")&&e.editor.util.IsEmptyNode(v)){v.innerHTML="";var A=e.document.createElement("br");v.appendChild(A);e.editor.selection.SetAfter(A)}}setTimeout(function(){if(v){e.editor.selection.SelectNode(v)}},10);return true}},state:function(t,n,r,i){n=typeof n==="string"?n.toUpperCase():n;var o=false,a=e.editor.selection.GetSelectedNode();if(a&&a.nodeName){if(a.nodeName!=n){a=BX.findParent(a,function(e){return e.nodeName==n},e.document.body)}o=a&&a.tagName==n?a:false}else{var l=e.editor.selection.GetRange(),s=e.editor.selection.GetCommonAncestorForRange(l);if(s.nodeName==n){o=s}}return o},value:BX.DoNothing,removeBrBeforeAndAfter:l,addBrBeforeAndAfter:a}},GetRemoveFormat:function(){var e={B:1,STRONG:1,I:1,EM:1,U:1,DEL:1,S:1,STRIKE:1,A:1,SPAN:1,CODE:1,NOBR:1,Q:1,FONT:1,CENTER:1,CITE:1},t={H1:1,H2:1,H3:1,H4:1,H5:1,H6:1,DIV:1,P:1,LI:1,UL:1,OL:1,MENU:1,BLOCKQUOTE:1,PRE:1},n=this;function r(r,i){if(!r)return;var o=r.nodeName;if(e[o]){n.editor.util.ReplaceWithOwnChildren(r)}else if(t[o]){n.actions.formatBlock.addBrBeforeAndAfter(r);n.editor.util.ReplaceWithOwnChildren(r)}else{r.removeAttribute("style");r.removeAttribute("class");r.removeAttribute("align");if(n.editor.bbCode&&r.nodeName=="TABLE"){r.removeAttribute("align")}}}function i(e){return BX.findParent(e,function(e){return e.nodeName=="TABLE"},n.editor.GetIframeDoc().body)}function o(e){var t,r,i=e.parentNode;while(i&&i.nodeName!=="BODY"){t=i.previousSibling&&!n.editor.util.IsEmptyNode(i.previousSibling);r=i.nextSibling&&!n.editor.util.IsEmptyNode(i.nextSibling);if(t||r||i.parentNode.nodeName=="BODY"){break}i=i.parentNode}return i}function a(e){var t=n.editor.GetIframeDoc(),r=BX.findParent(e,function(e){return e.nodeName=="UL"||e.nodeName=="OL"||e.nodeName=="MENU"},t.body);if(r){var i,o,a=r.cloneNode(false),l=r.cloneNode(false),s=true;BX.cleanNode(a);BX.cleanNode(l);for(i=0;i<r.childNodes.length;i++){o=r.childNodes[i];if(o==e){s=false}if(o.nodeName=="LI"){if(!n.editor.util.IsEmptyNode(o,true,true)){(s?a:l).appendChild(o.cloneNode(true))}}}if(a.childNodes.length>0){r.parentNode.insertBefore(a,r)}r.parentNode.insertBefore(e,r);if(l.childNodes.length>0){r.parentNode.insertBefore(l,r)}BX.remove(r);return true}return false}function l(e,t){if(e&&e.length>0){var i,o,a=[];for(i=0,o=e.length;i<o;i++){if(!n.editor.util.CheckSurrogateNode(e[i])){a.push({node:e[i],nesting:n.editor.util.GetNodeDomOffset(e[i])})}}a=a.sort(function(e,t){return t.nesting-e.nesting});for(i=0,o=a.length;i<o;i++){r(a[i].node,t)}}}function s(e){var t,r=false,i=n.editor.selection.SelectNode(e),o=i.getNodes([1]);if(e.nodeType==1){for(t=0;t<o.length;t++){if(o[t]==e){r=true;break}}if(!r){o=[e].concat(o)}}if(!o||typeof o!="object"||o.length==0){o=[e]}return o}function d(e,t){var n=[];if(e&&(!t||e==t)){n.push(e)}else if(!e&&t){n.push(t)}return n}return{exec:function(e,t){var r=n.editor.selection.GetRange();if(!r||n.editor.iframeView.IsEmpty())return;var d=true,f,c,u,m,h,g=r.getNodes([1]),N=n.editor.GetIframeDoc();if(g.length==0){c=r.getNodes([3]);if(c&&c.length==1){u=c[0]}if(!u&&r.startContainer==r.endContainer){if(r.startContainer.nodeType==3){u=r.startContainer}else{d=false;g=s(r.startContainer)}}if(u&&g.length==0){m=o(u);if(m&&(m.nodeName!="BODY"||r.collapsed)){d=false;g=s(m)}}}else{var p=false,v=[],B=i(r.startContainer),b=i(r.endContainer);if(B){v.push({startContainer:r.startContainer,startOffset:r.startOffset,end:B});r.setStartAfter(B);p=true}if(b){p=true;v.push({start:b,endContainer:r.endContainer,endOffset:r.endOffset});r.setEndBefore(b)}var C=n.editor.util.FindParentEx(r.startContainer,function(e){return e.nodeName=="UL"||e.nodeName=="OL"||e.nodeName=="MENU"},N.body),x=n.editor.util.FindParentEx(r.endContainer,function(e){return e.nodeName=="UL"||e.nodeName=="OL"||e.nodeName=="MENU"},N.body);if(C){r.setStartBefore(C);if(!x)r.setEndAfter(C);p=true}if(x){p=true;r.setEndAfter(x);if(!C)r.setStartBefore(x)}if(p){n.editor.selection.SetSelection(r);g=r.getNodes([1])}}if(d){h=N.createElement("span");n.editor.selection.Surround(h,r);g=s(h)}if(g&&g.length>0){n.editor.selection.ExecuteAndRestoreSimple(function(){l(g,N)})}if(v&&v.length>0){var I=r.cloneRange();for(f=0;f<v.length;f++){if(v[f].start){I.setStartBefore(v[f].start)}else{I.setStart(v[f].startContainer,v[f].startOffset)}if(v[f].end){I.setEndAfter(v[f].end)}else{I.setEnd(v[f].endContainer,v[f].endOffset)}n.editor.selection.SetSelection(I);l(I.getNodes([1]),N)}n.editor.selection.SetSelection(r)}if(d&&h&&h.parentNode){if(a(h)){n.editor.selection.SelectNode(h)}n.editor.selection.ExecuteAndRestoreSimple(function(){n.editor.util.ReplaceWithOwnChildren(h)})}n.actions.formatBlock.exec("formatBlock",null)},state:BX.DoNothing,value:BX.DoNothing}},GetBold:function(){var e=this;return{exec:function(t,n){if(!e.editor.bbCode||!e.editor.synchro.IsFocusedOnTextarea()){return e.actions.formatInline.exec(t,n,"b")}else{return e.actions.formatBbCode.exec(t,{tag:"B"})}},state:function(t,n){return e.actions.formatInline.state(t,n,"b")},value:BX.DoNothing}},GetItalic:function(){var e=this;return{exec:function(t,n){if(!e.editor.bbCode||!e.editor.synchro.IsFocusedOnTextarea()){return e.actions.formatInline.exec(t,n,"i")}else{return e.actions.formatBbCode.exec(t,{tag:"I"})}},state:function(t,n){return e.actions.formatInline.state(t,n,"i")},value:BX.DoNothing}},GetUnderline:function(){var e=this;return{exec:function(t,n){if(!e.editor.bbCode||!e.editor.synchro.IsFocusedOnTextarea()){return e.actions.formatInline.exec(t,n,"u")}else{return e.actions.formatBbCode.exec(t,{tag:"U"})}},state:function(t,n){return e.actions.formatInline.state(t,n,"u")},value:BX.DoNothing}},GetStrikeout:function(){var e=this;return{exec:function(t,n){if(!e.editor.bbCode||!e.editor.synchro.IsFocusedOnTextarea()){return e.actions.formatInline.exec(t,n,"del")}else{return e.actions.formatBbCode.exec(t,{tag:"S"})}},state:function(t,n){return e.actions.formatInline.state(t,n,"del")},value:BX.DoNothing}},GetFontSize:function(){var e=this;return{exec:function(t,n){var r;if(!e.editor.bbCode||!e.editor.synchro.IsFocusedOnTextarea()){if(n>0)r=e.actions.formatInline.exec(t,n,"span",{fontSize:n+"pt"});else r=e.actions.formatInline.exec(t,n,"span",{fontSize:null},null,{bClear:true})}else{r=e.actions.formatBbCode.exec(t,{tag:"SIZE",value:n+"pt"})}return r},state:function(t,n){return e.actions.formatInline.state(t,n,"span",{fontSize:n+"pt"})},value:BX.DoNothing}},GetForeColor:function(){var e=this;function t(){var t=e.editor.GetIframeDoc(),n,r,i=[],o=e.editor.selection.GetRange();if(o){var a=o.getNodes([1]);if(a.length==0&&o.startContainer==o.endContainer&&o.startContainer.nodeName!="BODY"){a=[o.startContainer]}for(n=0;n<a.length;n++){r=BX.findParent(a[n],function(e){return e.nodeName=="LI"&&e.style&&e.style.color},t.body);if(r){i.push(r)}}}return i.length===0?false:i}function n(t){var n=e.editor.GetIframeDoc(),r=e.editor.selection.GetSelectedNode(),i,o,a,l,s,d,f;if(r&&(r.nodeType===3||r.nodeName=="SPAN")){s=r.nodeName=="SPAN"?r:BX.findParent(r,{tag:"span"},n.body);if(s&&s.style.color){d=BX.findParent(s,{tag:"li"},n.body);if(d){if(d.childNodes.length==1&&d.firstChild==s){e.editor.selection.ExecuteAndRestoreSimple(function(){d.style.color=t;s.style.color="";if(BX.util.trim(s.style.cssText)==""){e.editor.util.ReplaceWithOwnChildren(s)}})}}}}else{if(!r){l=e.editor.selection.GetRange();f=l.getNodes([1])}else{f=[r]}for(i=0;i<f.length;i++){if(f[i]&&f[i].nodeName=="LI"){f[i].style.color=t;a=BX.findChild(f[i],function(e){return e.nodeName=="SPAN"},true,true);e.editor.selection.ExecuteAndRestoreSimple(function(){for(o=0;o<a.length;o++){if(a[o]&&a[o].parentNode&&a[o].parentNode.parentNode&&a[o].parentNode.parentNode.parentNode){try{a[o].style.color="";if(BX.util.trim(a[o].style.cssText)==""){e.editor.util.ReplaceWithOwnChildren(a[o])}}catch(t){}}}})}}}}return{exec:function(t,r){var i;if(!e.editor.bbCode||!e.editor.synchro.IsFocusedOnTextarea()){if(r==""){i=e.actions.formatInline.exec(t,r,"span",{color:null},null,{bClear:true})}else{i=e.actions.formatInline.exec(t,r,"span",{color:r});n(r)}}else if(r){i=e.actions.formatBbCode.exec(t,{tag:"COLOR",value:r})}return i},state:function(n,r){var i=e.actions.formatInline.state(n,r,"span",{color:r});if(!i){i=t()}return i},value:BX.DoNothing}},GetBackgroundColor:function(){var e=this;return{exec:function(t,n){var r;if(n==""){r=e.actions.formatInline.exec(t,n,"span",{backgroundColor:null},null,{bClear:true})}else{r=e.actions.formatInline.exec(t,n,"span",{backgroundColor:n})}return r},state:function(t,n){return e.actions.formatInline.state(t,n,"span",{backgroundColor:n})},value:BX.DoNothing}},GetCreateLink:function(){var e=["title","id","name","target","rel"],t=this;return{exec:function(n,r){if(t.editor.bbCode&&t.editor.synchro.IsFocusedOnTextarea()){t.editor.textareaView.Focus();var i="[URL="+r.href+"]"+(r.text||r.href)+"[/URL]";t.editor.textareaView.WrapWith(false,false,i)}else{t.editor.iframeView.Focus();var o,a=typeof r==="object"?r:{href:r},l,s,d=0,f,c;function u(t,n){var r;t.removeAttribute("class");t.removeAttribute("target");for(r in n){if(n.hasOwnProperty(r)&&BX.util.in_array(r,e)){if(n[r]==""||n[r]==undefined){t.removeAttribute(r)}else{t.setAttribute(r,n[r])}}}if(n.className)t.className=n.className;t.href=n.href||"";if(n.noindex){t.setAttribute("data-bx-noindex","Y")}else{t.removeAttribute("data-bx-noindex")}}c=t.actions.formatInline.state(n,r,"a");if(c){for(l=0;l<c.length;l++){s=c[l];if(s){u(s,a);f=s;d++}}if(d===1&&f&&(f.querySelector&&!f.querySelector("*"))&&a.text!=""){t.editor.util.SetTextContent(f,a.text)}o=f;if(o)t.editor.selection.SetAfter(o);setTimeout(function(){if(o)t.editor.selection.SetAfter(o)},10)}else{var m="_bx-editor-temp-"+Math.round(Math.random()*1e6),h,g,N;t.actions.formatInline.exec(n,r,"A",false,m);if(t.document.querySelectorAll){c=t.document.querySelectorAll("A."+m)}else{c=[]}for(l=0;l<c.length;l++){s=c[l];if(s){u(s,a)}}o=s;if(c.length===1){N=t.editor.util.GetTextContent(s);g=N===""||N===t.editor.INVISIBLE_SPACE;if(N!=a.text){t.editor.util.SetTextContent(s,a.text||a.href)}if(s.querySelector&&!s.querySelector("*")&&g){t.editor.util.SetTextContent(s,a.text||a.href)}}if(s){if(s.nextSibling&&s.nextSibling.nodeType==3&&t.editor.util.IsEmptyNode(s.nextSibling)){h=s.nextSibling}else{h=t.editor.util.GetInvisibleTextNode()}t.editor.util.InsertAfter(h,s);o=h}if(o)t.editor.selection.SetAfter(o);setTimeout(function(){if(o)t.editor.selection.SetAfter(o)},10)}}},state:function(e,n){return t.actions.formatInline.state(e,n,"a")},value:BX.DoNothing}},GetRemoveLink:function(){var e=this;return{exec:function(t,n){e.editor.iframeView.Focus();var r,i,o;if(n&&typeof n=="object"){o=n}else{o=e.actions.formatInline.state(t,n,"a")}if(o){e.editor.selection.ExecuteAndRestoreSimple(function(){for(r=0;r<o.length;r++){i=o[r];if(i){e.editor.util.ReplaceWithOwnChildren(o[r])}}})}},state:function(e,t){},value:BX.DoNothing}},GetInsertHTML:function(){var e=this;return{exec:function(t,n){e.editor.iframeView.Focus();if(e.IsSupportedByBrowser(t))e.document.execCommand(t,false,n);else e.editor.selection.InsertHTML(n)},state:function(){return false},value:BX.DoNothing}},GetInsertImage:function(){var e=["title","alt","width","height","align"],t=this;return{exec:function(n,r){if(r.src=="")return;if(t.editor.bbCode&&t.editor.synchro.IsFocusedOnTextarea()){t.editor.textareaView.Focus();var i="";if(r.width)i+=" WIDTH="+parseInt(r.width);if(r.height)i+=" HEIGHT="+parseInt(r.height);var o="[IMG"+i+"]"+r.src+"[/IMG]";t.editor.textareaView.WrapWith(false,false,o);return}t.editor.iframeView.Focus();var a=typeof r==="object"?r:{src:r},l=r.image||t.actions.insertImage.state(n,r),s,d;function f(n,r){var i,o;n.removeAttribute("class");n.setAttribute("data-bx-orig-src",r.src||"");for(i in r){if(r.hasOwnProperty(i)&&BX.util.in_array(i,e)){if(r[i]==""||r[i]==undefined){n.removeAttribute(i)}else{o=n.getAttribute("data-bx-app-ex-"+i);if(!o||t.editor.phpParser.AdvancedPhpGetFragmentByCode(o,true)!=r[i]){n.setAttribute(i,r[i]);if(o){n.removeAttribute("data-bx-app-ex-"+i)}}}}}if(r.className){n.className=r.className}o=n.getAttribute("data-bx-app-ex-src");if(!o||t.editor.phpParser.AdvancedPhpGetFragmentByCode(o,true)!=r.src){n.src=r.src||"";if(o){n.removeAttribute("data-bx-app-ex-src")}}}if(!l){l=t.document.createElement("IMG");f(l,a);t.editor.selection.InsertNode(l)}else{f(l,a)}var c=l,u=l.parentNode&&l.parentNode.nodeName=="A"?l.parentNode:null;if(a.link){if(u){u.href=a.link}else{u=t.document.createElement("A");u.href=a.link;l.parentNode.insertBefore(u,l);u.appendChild(l)}c=u}else if(u){t.editor.util.ReplaceWithOwnChildren(u)}if(BX.browser.IsIE()){t.editor.selection.SetAfter(l);d=l.nextSibling;if(d&&d.nodeType==3&&t.editor.util.IsEmptyNode(d))s=d;else s=t.editor.util.GetInvisibleTextNode();t.editor.selection.InsertNode(s);c=s}t.editor.selection.SetAfter(c);t.editor.util.Refresh()},state:function(){var e,n,r;if(!t.editor.util.DocumentHasTag(t.document,"IMG"))return false;e=t.editor.selection.GetSelectedNode();if(!e)return false;if(e.nodeName==="IMG")return e;if(e.nodeType!==1)return false;n=BX.util.trim(t.editor.selection.GetText());if(n&&n!=t.editor.INVISIBLE_SPACE)return false;r=t.editor.selection.GetNodes(1,function(e){return e.nodeName==="IMG"});if(r.length!==1)return false;return r[0]},value:BX.DoNothing}},GetInsertLineBreak:function(){var e=this,t="<br>"+(BX.browser.IsOpera()?" ":"");return{exec:function(n){if(e.IsSupportedByBrowser(n)){e.document.execCommand(n,false,null)}else{e.actions.insertHTML.exec("insertHTML",t)}if(BX.browser.IsChrome()||BX.browser.IsSafari()||BX.browser.IsIE10()){e.editor.selection.ScrollIntoView()}},state:BX.DoNothing,value:BX.DoNothing}},GetInsertTable:function(){var e=this,t=["title","id","border","cellSpacing","cellPadding","align"];function n(t){var n=e.document.createElement("TH");while(t.firstChild)n.appendChild(t.firstChild);e.editor.util.ReplaceNode(t,n)}function r(t){var n=e.document.createElement("TD");while(t.firstChild)n.appendChild(t.firstChild);e.editor.util.ReplaceNode(t,n)}function i(i,o){var a;i.removeAttribute("class");for(a in o){if(o.hasOwnProperty(a)&&BX.util.in_array(a,t)){if(o[a]==""||o[a]==undefined){i.removeAttribute(a)}else{i.setAttribute(a,o[a])}}}if(o.className){i.className=o.className}i.removeAttribute("data-bx-no-border");if(i.getAttribute("border")==0||!i.getAttribute("border")){i.removeAttribute("border");i.setAttribute("data-bx-no-border","Y")}if(o.width){if(parseInt(o.width)==o.width){o.width=o.width+"px"}if(i.getAttribute("width")){i.setAttribute("width",o.width)}else{i.style.width=o.width}}if(o.height){if(parseInt(o.height)==o.height){o.height=o.height+"px"}if(i.getAttribute("height")){i.setAttribute("height",o.height)}else{i.style.height=o.height}}var l,s,d;for(l=0;l<i.rows.length;l++){for(s=0;s<i.rows[l].cells.length;s++){d=i.rows[l].cells[s];if((o.headers=="top"||o.headers=="topleft")&&l==0||(o.headers=="left"||o.headers=="topleft")&&s==0){n(d)}else if(d.nodeName=="TH"){r(d)}}}var f=BX.findChild(i,{tag:"CAPTION"},false);if(o.caption){if(f){f.innerHTML=BX.util.htmlspecialchars(o.caption)}else{f=e.document.createElement("CAPTION");f.innerHTML=BX.util.htmlspecialchars(o.caption);i.insertBefore(f,i.firstChild)}}else if(f){BX.remove(f)}}return{exec:function(t,n){if(!e.editor.bbCode||!e.editor.synchro.IsFocusedOnTextarea()){if(!n||!n.rows||!n.cols){return false}e.editor.iframeView.Focus();var r=n.table||e.actions.insertTable.state(t,n);n.rows=parseInt(n.rows)||1;n.cols=parseInt(n.cols)||1;if(n.align=="left"||e.editor.bbCode){n.align=""}if(!r){r=e.document.createElement("TABLE");var o=r.appendChild(e.document.createElement("TBODY")),a,l,s,d;n.rows=parseInt(n.rows)||1;n.cols=parseInt(n.cols)||1;for(a=0;a<n.rows;a++){s=o.insertRow(-1);for(l=0;l<n.cols;l++){d=BX.adjust(s.insertCell(-1),{html:"&nbsp;"})}}i(r,n);e.editor.selection.InsertNode(r);var f=e.editor.util.GetNextNotEmptySibling(r);if(!f){e.editor.util.InsertAfter(BX.create("BR",{},e.document),r)}if(f&&f.nodeName=="BR"&&!f.nextSibling){e.editor.util.InsertAfter(e.editor.util.GetInvisibleTextNode(),f)}}else{i(r,n)}var c=r.rows[0].cells[0].firstChild;if(c){e.editor.selection.SetAfter(c)}setTimeout(function(){e.editor.util.Refresh(r)},10)}else{e.editor.textareaView.Focus();var u="",m,h,g=e.editor.INVISIBLE_SPACE;if(n.rows>0&&n.cols>0){u+="[TABLE]\n";for(m=0;m<n.rows;m++){u+="	[TR]\n";for(h=0;h<n.cols;h++){u+="		[TD]"+g+"[/TD]\n"}u+="	[/TR]\n"}u+="[/TABLE]\n"}e.editor.textareaView.WrapWith(false,false,u)}},state:function(t,n){var r,i;if(!e.editor.util.DocumentHasTag(e.document,"TABLE")){return false}r=e.editor.selection.GetSelectedNode();if(!r){return false}if(r.nodeName==="TABLE"){return r}if(r.nodeType!==1){return false}i=e.editor.selection.GetNodes(1,function(e){return e.nodeName==="TABLE"});if(i.length!==1){return false}return i[0]},value:BX.DoNothing}},GetInsertList:function(e){var t=this;var n=!!e.bOrdered,r=n?"OL":"UL",i=n?"UL":"OL";function o(e){var n=e.nextSibling;while(n&&n.nodeType==3&&t.editor.util.IsEmptyNode(n,true)){n=n.nextSibling}return n}function a(e){if(e.nodeName!=="MENU"&&e.nodeName!=="UL"&&e.nodeName!=="OL"){return}var n=t.document.createDocumentFragment(),r=e.previousSibling,i,o,a,l;if(r&&!t.editor.util.IsBlockElement(r)&&!t.editor.util.IsEmptyNode(r,true)){n.appendChild(t.document.createElement("BR"))}while(l=e.firstChild){o=l.lastChild;while(i=l.firstChild){if(i.nodeName=="I"&&i.innerHTML==""&&i.className!=""){BX.remove(i);i=l.firstChild;if(!i)break}a=i===o&&!t.editor.util.IsBlockElement(i)&&i.nodeName!=="BR";n.appendChild(i);if(a){n.appendChild(t.document.createElement("BR"))}}l.parentNode.removeChild(l)}var s=t.editor.util.GetNextNotEmptySibling(e);if(s&&s.nodeName=="BR"&&n.lastChild&&n.lastChild.nodeName=="BR"){BX.remove(n.lastChild)}e.parentNode.replaceChild(n,e)}function l(e,n){if(!e||!e.parentNode)return false;var r=e.nodeName.toUpperCase();if(r==="UL"||r==="OL"||r==="MENU"){return e}var i=t.document.createElement(n),o,a;while(e.firstChild){a=a||i.appendChild(t.document.createElement("li"));o=e.firstChild;if(t.editor.util.IsBlockElement(o)){a=a.firstChild?i.appendChild(t.document.createElement("li")):a;a.appendChild(o);a=null;continue}if(o.nodeName==="BR"){a=a.firstChild?null:a;e.removeChild(o);continue}a.appendChild(o)}e.parentNode.replaceChild(i,e);return i}function s(e){return e.nodeName=="OL"||e.nodeName=="UL"||e.nodeName=="MENU"}function d(e,n){if(!n){n=t.editor.selection.GetSelectedNode()}if(!n){var r=t.editor.selection.GetRange(),i=t.editor.selection.GetCommonAncestorForRange(r);if(i&&s(i)){n=i}else{var o,a=true,l,d,f=r.getNodes([1]),c=f.length;if(i){l=d=BX.findParent(i,s,t.document.body)}if(!d){for(o=0;o<c;o++){d=BX.findParent(f[o],s,i);if(!d||l&&d!=l){a=false;break}l=d}}if(l){n=l}}}return n&&n.nodeName==e?n:BX.findParent(n,{tagName:e},t.document.body)}function f(e,t){if(!e)return false;if(!t)t={tag:"I",remove:true};var n,r,i,o=e.ownerDocument;for(n=0;n<e.childNodes.length;n++){r=e.childNodes[n];if(r&&r.nodeType==1&&r.nodeName=="LI"){i=r.firstChild;if(i.nodeName==t.tag&&i.innerHTML==""){if(t.remove)BX.remove(i);else i.className=t.className}else{r.insertBefore(BX.create(t.tag,{props:{className:t.className}},o),i)}}}}function c(e){if(!e)return false;var t,n,r;for(t=0;t<e.childNodes.length;t++){n=e.childNodes[t];if(n&&n.nodeType==1&&n.nodeName=="LI"){r=n.firstChild;if(r.nodeName=="I"&&r.innerHTML==""&&r.className!==""){return r.className}}}return false}function u(e,n,r){if(e&&(e.nodeName=="UL"||e.nodeName=="OL")){var i,o,a,l=e.ownerDocument,s;for(i=0;i<e.childNodes.length;i++){o=e.childNodes[i];if(o&&o.nodeType==1&&o.nodeName=="LI"){a=o.firstChild;if(a.nodeName=="I"&&a.innerHTML==""&&a.className!==""){if(!n)n=a.className}else if(a.nodeName=="I"&&a.innerHTML==""&&n){a.className=n;s=a}else if(a&&n){s=o.insertBefore(BX.create("I",{props:{className:n}},l),a)}}}if(r&&s)t.editor.selection._MoveCursorAfterNode(s)}}return{exec:function(e,o){if(!t.editor.bbCode||!t.editor.synchro.IsFocusedOnTextarea()){var s=t.editor.selection.GetRange();if(t.IsSupportedByBrowser(e)&&s.collapsed){t.document.execCommand(e,false,null)}else{var f=t.editor.selection.GetSelectedNode(),c=d(r,f),u=d(i,f),m,h;if(c){t.editor.selection.ExecuteAndRestoreSimple(function(){a(c)})}else if(u){t.editor.selection.ExecuteAndRestoreSimple(function(){t.editor.util.RenameNode(u,r)})}else{h=t.document.createElement("span");t.editor.selection.Surround(h);m=h.innerHTML===""||h.innerHTML===t.editor.INVISIBLE_SPACE;t.editor.selection.ExecuteAndRestoreSimple(function(){c=l(h,r)});if(c){var g=0,N;while(g<c.childNodes.length){N=c.childNodes[g];if(N.nodeName=="LI"){if(t.editor.util.IsEmptyNode(N,true,true)){BX.remove(N);continue}g++}else if(N.nodeType==1){BX.remove(N)}}var p=t.editor.util.GetPreviousNotEmptySibling(c);if(p&&(p.nodeName=="BLOCKQUOTE"||p.nodeName=="PRE"||p.nodeName=="UL"||p.nodeName=="OL")&&c.childNodes[0]&&BX.findChild(c.childNodes[0],{tag:p.nodeName})){if(BX.util.trim(t.editor.util.GetTextContent(p))==""){BX.remove(p)}}}if(m&&c&&c.querySelector){t.editor.selection.SelectNode(c.querySelector("li"))}}}}else{if(o&&o.items){t.editor.textareaView.Focus();var v="[LIST"+(n?"=1":"")+"]\n",B;for(B=0;B<o.items.length;B++){v+="	[*]"+o.items[B]+"\n"}v+="[/LIST]\n";t.editor.textareaView.WrapWith(false,false,v)}}},state:function(){return d(r)||false},value:BX.DoNothing,customBullit:f,getCustomBullitClass:c,checkCustomBullitList:u}},GetAlign:function(){var e="bx-align-tmp",t="data-bx-checked-align-list",n="left",r={TD:1,TR:1,TH:1,TABLE:1,TBODY:1,CAPTION:1,COL:1,COLGROUP:1,TFOOT:1,THEAD:1},i={IMG:1,P:1,DIV:1,TABLE:1,H1:1,H2:1,H3:1,H4:1,H5:1,H6:1},o=this;function a(e){var t=e.nodeName,n,r=false;if(e.nodeType===1){n=e.style.textAlign;if(e.style.textAlign){r={node:e,style:n}}if(i[t]){n=e.getAttribute("align");if(n){if(r){r.attribute=n}else{r={node:e,attribute:n}}}}}return r}function l(e){return e&&(e.nodeName=="TD"||e.nodeName=="TH")}function s(e,t){e.setAttribute("data-bx-tmp-align",t);e.style.textAlign=t}function d(e,t){if(t=="left"||t=="justify"||o.editor.bbCode){e.removeAttribute("align")}else{e.setAttribute("align",t)}}function f(e,t){var n,r,i=true,o=e.getElementsByTagName("TD");for(r=0;r<o.length;r++){if(o[r].getAttribute("data-bx-tmp-align")!=t){i=false;break}}if(i){n=e.getElementsByTagName("TH");for(r=0;r<n.length;r++){if(n[r].getAttribute("data-bx-tmp-align")!=t){i=false;break}}}if(i){d(e,t)}}function c(e,t){var n=BX.create("DIV",{style:{textAlign:t},html:e.innerHTML},o.editor.GetIframeDoc());e.innerHTML="";e.appendChild(n);return n}function u(e,t){var n=BX.create("DIV",{style:{textAlign:t}},o.editor.GetIframeDoc());e.parentNode.insertBefore(n,e);n.appendChild(e);return n}function m(e,n,r){var i=o.editor.GetIframeDoc(),a=o.editor.bbCode;if(!e&&n){e=BX.findParent(n,function(e){return e.nodeName=="OL"||e.nodeName=="UL"||e.nodeName=="MENU"},i)}if(e&&!e.getAttribute(t)){var l,s=true,d=e.getElementsByTagName("LI");for(l=0;l<d.length;l++){if(d[l].style.textAlign!==r){s=false;break}}if(a){e.style.textAlign="";if(e.style.cssText==""){e.removeAttribute("style")}h(e);u(e,r)}else if(s){e.style.textAlign=r;h(e)}e.setAttribute(t,"Y");

return e}return false}function h(e){var t,n=e.getElementsByTagName("LI");for(t=0;t<n.length;t++){n[t].style.textAlign="";if(n[t].style.cssText==""){n[t].removeAttribute("style")}}}function g(e,t){if(t&&t.nodeName=="TABLE"){var n,r=false;for(n=0;n<e.length;n++){if(e[n]==t){r=true;break}}if(!r){e.push(t)}}return e}return{exec:function(n,i){var a;if(!o.editor.bbCode||!o.editor.synchro.IsFocusedOnTextarea()){var N,p="P",v=o.editor.selection.GetRange(),B=false,b=false,C=false,x=o.editor.selection.GetBookmark(),I=o.editor.selection.GetSelectedNode();if(I){if(o.editor.util.IsBlockNode(I)){B=I}else if(I.nodeType==1&&r[I.nodeName]){b=I;a=true;setTimeout(function(){o.editor.selection.SelectNode(b);if(b.nodeName=="TABLE"){d(b,i)}},10)}else{if(I.nodeName=="LI"){C=I}else if(I.nodeName=="OL"||I.nodeName=="UL"||I.nodeName=="MENU"){if(o.editor.bbCode){u(I,i);I.style.textAlign=""}else{I.style.textAlign=i}a=true;h(I);setTimeout(function(){o.editor.selection.SelectNode(I)},10)}else{C=BX.findParent(I,function(e){return e.nodeName=="LI"},o.document.body)}if(C){if(o.editor.bbCode){c(C,i);C.style.textAlign=""}else{C.style.textAlign=i}a=true;setTimeout(function(){o.editor.selection.SelectNode(C)},10)}else{B=BX.findParent(I,function(e){return o.editor.util.IsBlockNode(e)&&!o.actions.quote.checkNode(e)},o.document.body)}}}else{var S=[],y=false,T=[],A=[],G=v.getNodes([1]);for(N=0;N<G.length;N++){if(l(G[N])){S=g(S,BX.findParent(G[N],{tagName:"TABLE"}));s(G[N],i);a=true}if(G[N].nodeName=="TABLE"){S=g(S,G[N]);y=true}else if(G[N].nodeName=="OL"||G[N].nodeName=="UL"||G[N].nodeName=="MENU"){G[N].style.textAlign=i;T.push(G[N]);a=true}else if(G[N].nodeName=="LI"){G[N].style.textAlign=i;a=true;A.push(G[N])}}for(N=0;N<S.length;N++){f(S[N],i)}if(a){var w=o.editor.selection.GetCommonAncestorForRange(v);if(w&&w.nodeName=="BODY"){a=false}}for(N=0;N<T.length;N++){h(T[N])}var E=[],L;for(N=0;N<A.length;N++){L=m(false,A[N],i);if(L){E.push(L)}}for(N=0;N<E.length;N++){E[N].removeAttribute(t)}}if(!a){if(B){if(o.editor.bbCode){c(B,i);B.style.textAlign=""}else{p=B.tagName!="DIV"?B.tagName:"P";a=o.actions.formatBlock.exec("formatBlock",p,null,{textAlign:i})}o.editor.util.Refresh(B)}else if(b){if(l(b)){s(b,i)}else{var X=BX.findChild(b,l,true,true),R=BX.findChild(b,l,true,true);for(N=0;N<X.length;N++){s(X[N],i)}for(N=0;N<R.length;N++){s(R[N],i)}}}else if(v.collapsed){a=o.actions.formatBlock.exec("formatBlock","P",e,{textAlign:i});var O,k=o.document.querySelectorAll("."+e);for(N=0;N<=k.length;N++){BX.removeClass(k[N],e);if(N==0){O=k[N].firstNode;if(!O)O=k[N].appendChild(o.editor.util.GetInvisibleTextNode());setTimeout(function(){if(O)o.editor.selection.SetAfter(O)},100)}}}else{var D=o.actions.insertImage.state();if(!a&&false){var F=true;G=v.getNodes([1]);if(G&&G.length>0){for(N=0;N<G.length;N++){if(G[N].nodeName=="P"){G[N].style.textAlign=i}else{F=false}}a=F}}if(!a){p=o.editor.bbCode?"DIV":"P";a=o.actions.formatBlock.exec("formatBlock",p,null,{textAlign:i},{leaveChilds:true});if(a&&typeof a=="object"&&a.nodeName==p){var M=0,P=2e3,U,H,V,_=false;if(a.firstChild&&a.firstChild.nodeName=="BLOCKQUOTE"){U=o.editor.util.GetPreviousNotEmptySibling(a);if(U&&U.nodeName=="BLOCKQUOTE"&&o.editor.util.IsEmptyNode(U)){BX.remove(U)}}N=0;while(N<a.childNodes.length||M>P){H=a.childNodes[N];if(o.editor.util.IsBlockNode(H)){H.style.textAlign=i;_=true;N++}else{if(!V||_){V=o.document.createElement(p);V.style.textAlign=i;a.insertBefore(V,H);N++}V.appendChild(H);_=false}M++}if(a.previousSibling&&a.previousSibling.nodeName=="P"&&o.editor.util.IsEmptyNode(a.previousSibling,true,true)){BX.remove(a.previousSibling)}if(a.nextSibling&&a.nextSibling.nodeName=="P"&&o.editor.util.IsEmptyNode(a.nextSibling,true,true)){BX.remove(a.nextSibling)}o.editor.util.ReplaceWithOwnChildren(a);setTimeout(function(){if(V)o.editor.selection.SelectNode(V)},100)}}if(D){o.editor.util.Refresh(D)}}}setTimeout(function(){o.editor.selection.SetBookmark(x)},10)}else{if(i){a=o.actions.formatBbCode.exec(n,{tag:i.toUpperCase()})}}return a},state:function(e,t){var r,i,l=o.editor.selection.GetSelectedNode();if(l){r=a(l);if(!r){i=BX.findParent(l,function(e){r=a(e);return r},o.document.body)}return{node:r?r.node:null,value:r?r.style||r.attribute:n,res:r}}else{var s={node:null,value:n,res:true},d=o.editor.selection.GetRange();if(!d.collapsed){var f,c="",u,m=d.getNodes([1]);for(u=0;u<m.length;u++){if(!o.editor.util.CheckSurrogateNode(m[u])&&m[u].nodeName!=="BR"&&o.editor.util.GetNodeDomOffset(m[u])==0){f=a(m[u]);t=f?f.style||f.attribute:n;if(!c){c=t}if(t!=c){s.res=false;break}}}if(s.res){s.value=c}}else{s.res=false}return s}},value:BX.DoNothing}},GetIndent:function(){var e=this;return{exec:function(t){var n=e.editor.selection.GetRange();if(n&&n.collapsed&&n.endContainer==n.startContainer&&BX.util.in_array(n.startContainer.nodeName,["TD","TR","TH"])&&arguments[1]!==n.startContainer.nodeName){var r="bxed_bogus_node_59811",i=n.startContainer.appendChild(BX.create("SPAN",{props:{id:r},html:"&nbsp;"},e.document));is_text=e.editor.util.GetInvisibleTextNode();if(i){n.setStartBefore(i);n.setEndAfter(i);e.editor.selection.SetSelection(n);return setTimeout(function(){e.actions.indent.exec(t,n.startContainer.nodeName);var i=e.editor.GetIframeElement(r);if(i){e.editor.selection.SetAfter(i);BX.remove(i)}},0)}}if(e.IsSupportedByBrowser(t)){e.document.execCommand(t)}else{e.actions.formatBlock.exec("formatBlock","BLOCKQUOTE")}var o=e.editor.selection.GetRange();if(o){var a,l=o.getNodes([1]);if(o.collapsed&&o.startContainer&&l.length==0){var s=BX.findParent(o.startContainer,{tag:"BLOCKQUOTE"});if(s){s.removeAttribute("style");var d=e.editor.util.GetInvisibleTextNode();s.appendChild(d);e.editor.selection.SetAfter(d)}}for(a=0;a<l.length;a++){if(l[a].nodeName=="BLOCKQUOTE"){l[a].removeAttribute("style")}}}},state:function(t,n){var r=false,i=e.editor.selection.GetRange();if(i){var o=e.editor.selection.GetCommonAncestorForRange(i);if(o&&o.nodeType==1&&o.nodeName==="BLOCKQUOTE"){r=o}}return r},value:BX.DoNothing}},GetOutdent:function(){var e=this;return{exec:function(t,n){var r,i="data-bx-tmp-flag",o=e.editor.GetIframeDoc(),a=o.getElementsByTagName("BLOCKQUOTE");{var l=[],s=o.getElementsByTagName("P");for(r=0;r<s.length;r++){s[r].setAttribute(i,"Y")}e.document.execCommand(t);s=o.getElementsByTagName("P");for(r=0;r<s.length;r++){if(!s[r].getAttribute(i)){l.push(s[r])}else{s[r].removeAttribute(i)}}e.editor.selection.ExecuteAndRestoreSimple(function(){for(r=0;r<l.length;r++){e.actions.formatBlock.addBrBeforeAndAfter(l[r]);e.editor.util.ReplaceWithOwnChildren(l[r])}})}},state:function(t,n){var r=e.editor.selection.GetRange();return false},value:BX.DoNothing}},GetFontFamily:function(){var e=this;return{exec:function(t,n){var r;if(!e.editor.bbCode||!e.editor.synchro.IsFocusedOnTextarea()){if(n)r=e.actions.formatInline.exec(t,n,"span",{fontFamily:n});else r=e.actions.formatInline.exec(t,n,"span",{fontFamily:null},null,{bClear:true})}else{return e.actions.formatBbCode.exec(t,{tag:"FONT",value:n})}return r},state:function(t,n){return e.actions.formatInline.state(t,n,"span",{fontFamily:n})},value:BX.DoNothing}},GetFormatStyle:function(){var e=this,t=this.editor.toolbar.controls.StyleSelector,n=t?t.checkedClasses:[],r=t?t.checkedTags:[];function i(t){if(t&&t.nodeType==1&&t.nodeName!=="BODY"&&(BX.util.in_array(t.nodeName,r)||BX.util.in_array(t.className,n))){return!e.editor.GetBxTag(t.id).tag}return false}return{exec:function(t,n){if(!n){return e.actions.removeFormat.exec("removeFormat")}else if(typeof n==="string"){return e.actions.formatBlock.exec("formatBlock",n)}else if(typeof n==="object"){if(n.tag=="UL"){var r=e.actions.insertUnorderedList.state();if(r&&n.className&&n.className.indexOf("~~")!==-1){var i=n.className.split("~~");if(i&&i.length>=2){var o=i[0],a=i[1];r.className=o;e.actions.insertUnorderedList.customBullit(r,{tag:"I",className:a,html:""})}}else if(r){r.className=n.className||"";e.actions.insertUnorderedList.customBullit(r,false)}}else if(n.tag){var l=n.className,s=n.tag.toUpperCase();if(s=="SPAN"){e.actions.formatInline.exec(t,n,s,false,l)}else{e.actions.formatBlock.exec("formatBlock",s,l,null,{nestedBlocks:false})}}}},state:function(t,n){var r=false,o=e.editor.selection.GetSelectedNode();if(o){if(i(o)){r=o}else{r=BX.findParent(o,i,e.document.body)}}else{var a=e.editor.selection.GetRange(),l=e.editor.selection.GetCommonAncestorForRange(a);if(i(l)){r=l}}return r},value:BX.DoNothing}},GetChangeTemplate:function(){var e=this;return{exec:function(t,n){e.editor.ApplyTemplate(n)},state:function(t,n){return e.editor.GetTemplateId()},value:BX.DoNothing}},GetSelectNode:function(){var e=this;return{exec:function(t,n){if(!e.editor.iframeView.IsFocused()){e.editor.iframeView.Focus()}if(n===false||n&&n.nodeName=="BODY"){if(e.IsSupportedByBrowser("SelectAll")){e.document.execCommand("SelectAll")}else{e.editor.selection.SelectNode(n)}}else{e.editor.selection.SelectNode(n)}},state:BX.DoNothing,value:BX.DoNothing}},GetUndoRedo:function(e){var t=this;return{exec:function(e){if(e=="doUndo"){t.editor.undoManager.Undo()}else if(e=="doRedo"){t.editor.undoManager.Redo()}},state:BX.DoNothing,value:BX.DoNothing}},GetUniversalFormatStyle:function(){var e="bx-tmp-ufs-class",t="data-bx-tmp-status",n=this;function r(r){var o=[];if(r&&r.length>0){var a,l,s=[],d,f;for(a=0,l=r.length;a<l;a++){if(!n.editor.util.CheckSurrogateNode(r[a])&&r[a].nodeName!=="BR"){r[a].setAttribute(t,"Y");s.push({node:r[a],nesting:n.editor.util.GetNodeDomOffset(r[a])})}}s=s.sort(function(e,t){return e.nesting-t.nesting});for(a=0,l=s.length;a<l;a++){d=s[a].node;f=d.getAttribute(t);if(f=="Y"&&!i(d)){BX.findChild(d,function(n){if(n.nodeType==1&&n.nodeName!=="BR"&&n.setAttribute){n.setAttribute(t,n.className==e?"GET_RID_OF":"SKIP")}return false},true,true);o.push(d)}}}return o}function i(e){var n=BX.findChild(e,function(e){return e.nodeType==1&&e.nodeName!=="BR"&&e.getAttribute&&e.getAttribute(t)!=="Y"},true,false);return!!n}function o(e,t,n){try{if(t!==false){if(t==""){e.removeAttribute("class")}else{e.className=t}}if(n!==false){if(n==""){e.removeAttribute("style")}else{e.style.cssText=n}}}catch(r){}}return{exec:function(i,a){if(a.nodes&&a.nodes.length>0){for(s=0;s<a.nodes.length;s++){o(a.nodes[s],a.className,a.style)}}else{n.actions.formatInline.exec(i,a,"span",false,e);if(document.querySelectorAll){var l=n.editor.GetIframeDoc().querySelectorAll("."+e);if(l){for(s=0;s<l.length;s++){d=l[s];if(BX.util.trim(d.innerHTML)==""){n.editor.util.ReplaceWithOwnChildren(d)}}}}var s,d,f=n.actions.universalFormatStyle.state(i),c=r(f);for(s=0;s<c.length;s++){o(c[s],a.className,a.style)}if(document.querySelectorAll){l=n.editor.GetIframeDoc().querySelectorAll("."+e);if(l){for(s=0;s<l.length;s++){d=l[s];if(d.getAttribute(t)=="GET_RID_OF"){n.editor.util.ReplaceWithOwnChildren(l[s])}else{o(l[s],a.className,a.style)}}}}}},state:function(e){var t=n.editor.selection.GetRange();if(t){var r,i,o,a=t.getNodes([1]);if(a.length==0){r=t.getNodes([3]);if(r&&r.length==1){i=r[0]}if(!i&&t.startContainer==t.endContainer){if(t.startContainer.nodeType==3){i=t.startContainer}else{n.editor.selection.SelectNode(t.startContainer);a=[t.startContainer]}}if(i&&a.length==0){o=i.parentNode;if(o){a=[o]}}}return a}},value:BX.DoNothing}},GetSubSup:function(e){var t=this;e=e=="sup"?"sup":"sub";return{exec:function(n,r){return t.actions.formatInline.exec(n,r,e)},state:function(n,r){return t.actions.formatInline.state(n,r,e)},value:BX.DoNothing}},GetQuote:function(){var e,t,n=this;function r(e){return e&&e.className=="bxhtmled-quote"&&e.nodeName=="BLOCKQUOTE"}function i(e){t=e}function o(){return t}function a(t){return e=t}return{exec:function(t){var r=false,i=o();if(n.editor.bbCode&&n.editor.synchro.IsFocusedOnTextarea()){n.editor.textareaView.Focus();if(i){r=n.editor.textareaView.WrapWith(false,false,"[QUOTE]"+i+"[/QUOTE]")}else{r=n.actions.formatBbCode.exec(t,{tag:"QUOTE"})}}else{if(i){if(!e&&n.editor.selection.lastCheckedRange&&n.editor.selection.lastCheckedRange.range){e=n.editor.selection.lastCheckedRange.range}n.editor.iframeView.Focus();if(e){n.editor.selection.SetSelection(e)}var a="bxq_"+Math.round(Math.random()*1e6);n.editor.InsertHtml('<blockquote id="'+a+'" class="bxhtmled-quote">'+i+"</blockquote>"+n.editor.INVISIBLE_SPACE,e);setTimeout(function(){var e=n.editor.GetIframeElement(a);if(e){var t=e.previousSibling;if(t&&t.nodeType==3&&n.editor.util.IsEmptyNode(t)&&t.previousSibling&&t.previousSibling.nodeName=="BR"){BX.remove(t)}e.id=null}},0)}else{r=n.actions.formatBlock.exec("formatBlock","blockquote","bxhtmled-quote",false,{range:e})}}e=null;return r},state:function(){return n.actions.formatBlock.state("formatBlock","blockquote","bxhtmled-quote")},value:BX.DoNothing,setExternalSelection:i,getExternalSelection:o,setRange:a,checkNode:r}},GetCode:function(){var e=this;return{exec:function(t){if(!e.editor.bbCode||!e.editor.synchro.IsFocusedOnTextarea()){var n=e.actions.code.state();if(n){var r=BX.util.trim(n.innerHTML);if(r=="<br>"||r===""){e.editor.selection.SetAfter(n);BX.remove(n)}else{e.editor.selection.ExecuteAndRestoreSimple(function(){n.className="";n=e.editor.util.RenameNode(n,"P")})}}else{e.actions.formatBlock.exec("formatBlock","pre","bxhtmled-code")}}else{return e.actions.formatBbCode.exec(t,{tag:"CODE"})}},state:function(){return e.actions.formatBlock.state("formatBlock","pre","bxhtmled-code")},value:BX.DoNothing}},GetInsertSmile:function(){var e=this;return{exec:function(t,n){var r=e.editor.smilesIndex[n];if(e.editor.bbCode&&e.editor.synchro.IsFocusedOnTextarea()){e.editor.textareaView.Focus();e.editor.textareaView.WrapWith(false,false," "+r.code+" ")}else{e.editor.iframeView.Focus();if(r){var i=BX.create("IMG",{props:{src:r.path,title:r.name||r.code}});e.editor.SetBxTag(i,{tag:"smile",params:r});e.editor.selection.InsertNode(i);var o=e.editor.iframeView.document.createTextNode(" ");i.parentNode.insertBefore(o,i);var a=e.editor.iframeView.document.createTextNode(" ");e.editor.util.InsertAfter(a,i);e.editor.selection.SetAfter(a);setTimeout(function(){e.editor.selection.SetAfter(i)},10)}}},state:BX.DoNothing,value:BX.DoNothing}},GetTableOperation:function(){var e=this,t="&nbsp;";function n(e){var t=e.rows;var n=[],r,i,o,a,l,s,d,f,c=-1;for(r=0;r<t.length;r++){c++;if(!n[c]){n[c]=[]}i=-1;for(o=0;o<t[r].cells.length;o++){a=t[r].cells[o];i++;while(n[c][i]){i++}d=isNaN(a.colSpan)?1:a.colSpan;f=isNaN(a.rowSpan)?1:a.rowSpan;for(l=0;l<f;l++){if(!n[c+l]){n[c+l]=[]}for(s=0;s<d;s++){n[c+l][i+s]=t[r].cells[o]}}i+=d-1}}return n}function r(e,t){var n,r,i=[];for(n=0;n<t.length;n++){for(r=0,l=t[n].length;r<l;r++){if(t[n][r]==e){i.push({r:n,c:r})}}}return i}function i(e){var t=[],n=[],r={cells:0},i;for(i=0;i<e.length;i++){r.cells++;r.maxRow=i===0?e[i].r:Math.max(e[i].r,r.maxRow);r.minRow=i===0?e[i].r:Math.min(e[i].r,r.minRow);r.maxCol=i===0?e[i].c:Math.max(e[i].c,r.maxCol);r.minCol=i===0?e[i].c:Math.min(e[i].c,r.minCol);if(!BX.util.in_array(e[i].r,t))t.push(e[i].r);if(!BX.util.in_array(e[i].c,n))n.push(e[i].c)}r.rows=t.length;r.cols=n.length;return r}function o(e,t,n){if(t){t=c(t,n);if(t&&!BX.util.in_array(t,e))e.push(t)}return e}function a(t,n){var r=[],i;if(BX.browser.IsFirefox()){var a,l,s,d,f=rangy.getNativeSelection(e.editor.sandbox.GetWindow());for(a=0;a<f.rangeCount;a++){l=f.getRangeAt(a);s=l.startContainer.nodeType===1?l.startContainer.childNodes[l.startOffset]:l.startContainer;d=l.endContainer.nodeType===1?l.endContainer.childNodes[l.endOffset]:l.endContainer;r=o(r,s,n);r=o(r,d,n)}}else{if(t.collapsed){i=c(t.startContainer);r=o(r,i,n)}else{var u=t.getNodes([1]);for(a=0;a<u.length;a++){if(u[a].nodeName=="TD"||u[a].nodeName=="TH"){r=o(r,u[a],n)}}}}return r}function s(e,i,o){var a=BX.findParent(e,{tag:"TD"});if(!a)return;var l=a.parentNode,s=o=="insertColumnLeft"?a.cellIndex:a.cellIndex+1,d=l.rowIndex,f=n(i),c=r(a,f);l.insertCell(s).innerHTML=t;var u,m,h,g,N,p=o=="insertColumnLeft"?c[0].c:c[0].c+1;for(N=0;N<i.rows.length;N++){u=i.rows[N];if(u.rowIndex==d){continue}m=0;h=0;for(h=0;h<u.cells.length;h++){g=u.cells[h];c=r(g,f);if(c[0].c>=p){m=g.cellIndex;break}m=h+1}u.insertCell(m).innerHTML="&nbsp;"}}function d(e,n,r){var i=BX.findParent(e,{tag:"TR"});if(!i||!n)return;var o,a,l=r=="insertRowUpper"?i.rowIndex:i.rowIndex+1,s=n.insertRow(l);for(o=0;o<i.cells.length;o++){a=s.insertCell(o);a.innerHTML=t;a.colSpan=i.cells[o].colSpan}}function f(e,n,r){var i=c(e,n);if(!i||!n)return;var o=i.parentNode,a=r=="insertCellLeft"?i.cellIndex:i.cellIndex+1;o.insertCell(a).innerHTML=t}function c(e,t){if(e.nodeName=="TD"||e.nodeName=="TH")return e;return BX.findParent(e,function(e){return e.nodeName=="TD"||e.nodeName=="TH"},t)}function u(e,t){var o,a,l=n(t),s=i(r(e[0],l)),d=s,f=false,c=true,u=true;for(a=1;a<e.length;a++){o=i(r(e[a],l));c=c&&o.rows==s.rows&&o.maxRow==s.maxRow&&o.minRow==s.minRow;u=u&&o.cols==s.cols&&o.maxCol==s.maxCol&&o.minCol==s.minCol;f=f||c&&Math.abs(o.minCol-d.maxCol)>1||u&&Math.abs(o.minRow-d.maxRow)>1||!c&&!u;d=o}return{sameCol:u,sameRow:c,gaps:f}}function m(e,t,n){if(!e)e=a(t,n);if(!e||e.length<2)return false;var r=u(e,n);return!r.gaps&&(!r.sameRow&&r.sameCol||r.sameRow&&!r.sameCol)}function h(e,t){var i=a(e,t);if(!i||i.length!==1)return false;var o=n(t),l=r(i[0],o);if(l.length<1)return false;var s,d,f=l[l.length-1].c,c=true,u;for(s=0;s<l.length;s++){if(l[s].c==f){if(o[l[s].r]&&o[l[s].r][l[s].c+1]){u=o[l[s].r][l[s].c+1];if(d===undefined)d=u;else if(d!==u)c=false}else{c=false}}}c=c&&d&&m([i[0],d],e,t);return c}function g(e,t){var i=a(e,t);if(!i||i.length!==1)return false;var o=n(t),l=r(i[0],o);if(l.length<1)return false;var s,d,f=l[l.length-1].r,c=true,u;for(s=0;s<l.length;s++){if(l[s].r==f){if(o[f+1]&&o[f+1][l[s].c]){u=o[f+1][l[s].c];if(d===undefined)d=u;else if(d!==u)c=false}else{c=false}}}c=c&&d&&m([i[0],d],e,t);return c}function N(e,t,n){if(!n)n=a(e,t);if(n.length<2)return;var r=u(n,t),i,o,l=0,s=0,d="";if(r.sameRow&&!r.sameCol&&!r.gaps){for(i=0;i<n.length;i++){d+=" "+BX.util.trim(n[i].innerHTML);o=n[i].parentNode;l+=n[i].colSpan;if(i>0)o.removeChild(n[i])}n[0].colSpan=l;n[0].innerHTML=BX.util.trim(d)}else if(!r.sameRow&&r.sameCol&&!r.gaps){for(i=0;i<n.length;i++){d+=" "+BX.util.trim(n[i].innerHTML);o=n[i].parentNode;s+=n[i].rowSpan;if(i>0)o.removeChild(n[i])}n[0].rowSpan=s;n[0].innerHTML=BX.util.trim(d)}else{alert(BX.message("BXEdTableMergeError"))}}function p(e,t){var n=a(e,t);if(!n||n.length!==1)return false;var r=BX.findParent(n[0],{tag:"TR"},t);if(n[0].cellIndex<r.cells.length-1){n.push(r.cells[n[0].cellIndex+1])}return N(e,t,n)}function v(e,t){var i=a(e,t);if(!i||i.length!==1)return false;var o=n(t),l=r(i[0],o),s,d,f=l[l.length-1].r,c=true,u;for(s=0;s<l.length;s++){if(l[s].r==f){if(o[f+1]&&o[f+1][l[s].c]){u=o[f+1][l[s].c];if(d===undefined)d=u;else if(d!==u)c=false}else{c=false}}}if(c){i.push(d);return N(e,t,i)}}function B(e,t){var n=a(e,t);if(!n||n.length!==1)return false;var r,i=[],o=n[0].parentNode;for(r=0;r<o.cells.length;r++){i.push(o.cells[r])}return N(e,t,i)}function b(e,t){var l=a(e,t);if(!l||l.length!==1)return false;var s,d,f=[],c=n(t),u=i(r(l[0],c));for(s=0;s<c.length;s++){for(d=u.minCol;d<=u.minCol;d++){f=o(f,c[s][d],t)}}return N(e,t,f)}function C(e,n){var r=a(e,n);if(!r||r.length!=1)return false;var i,o,l=0,s,d,f,c=r[0].colSpan,u=r[0].parentNode;for(i=0;i<=r[0].cellIndex;i++)l+=u.cells[i].colSpan;if(c>1){r[0].colSpan--}else{for(o=0;o<n.rows.length;o++){if(o==u.rowIndex)continue;s=0;d=n.rows[o];i=0;while(s<l&&i<d.cells.length)s+=d.cells[i++].colSpan;d.cells[--i].colSpan+=1}}f=u.insertCell(r[0].cellIndex+1);f.rowSpan=r[0].rowSpan;f.innerHTML=t}function x(e,i){var o=a(e,i);if(!o||o.length!=1)return false;var l,s,d,f,c,u,m,h,g=n(i),N=r(o[0],g),p=o[0].parentNode,v=p.rowIndex,B=o[0].cellIndex,b=N[0].r,C=N[0].c,x=true,I=true;for(l=1;l<N.length;l++){if(N[l].r!=b)I=false;if(N[l].c!=C)x=false}if(I){var S=i.insertRow(p.rowIndex+1),y=S.insertCell(-1);y.innerHTML=t;if(!x)y.colSpan=o[0].colSpan;for(s=0;s<=b;s++){f=i.rows[s];for(d=0;d<f.cells.length;d++){c=f.cells[d];if(s==v&&d==B)continue;m=s;if(c.rowSpan>1)m+=c.rowSpan-1;if(m>=b)c.rowSpan++}}}else{f=i.rows[v+--o[0].rowSpan];h=false;for(d=0;d<f.cells.length;d++){u=r(f.cells[d],g);for(l=0;l<u.length;l++){if(u[l].c>B)h=0;else if(u[l].c+1==B)h=f.cells[d].cellIndex+1;if(h!==false)break}}y=f.insertCell(h);y.innerHTML=t;if(!x)y.colSpan=o[0].colSpan}}function I(e,t){var i=a(e,t);if(!i||i.length!=1)return false;var o=i[0];if(!o)return false;var l,s,d=n(t),f=r(o,d),c;for(c=0;c<d.length;c++){s=d[c][f[0].c];if(s&&s.parentNode){l=s.parentNode;BX.remove(s);if(l.cells.length==0)BX.remove(l)}}if(t.rows.length==0)BX.remove(t)}function S(e,t){var n=a(e,t);if(!n||n.length!=1)return false;var r=n[0];if(!r)return false;BX.remove(r.parentNode);if(t.rows.length==0)BX.remove(t)}function y(e,t,n){if(!n){var r=a(e,t);if(!r||r.length!=1)return false;n=r[0]}if(!n)return false;var i=n.parentNode;BX.remove(n);if(i.cells.length==0)BX.remove(i);if(t.rows.length==0)BX.remove(t)}function T(e,t){var n=a(e,t);if(!n||n.length==1)return false;var r,i;for(r=0;r<n.length;r++){i=n[r].parentNode;BX.remove(n[r]);if(i.cells.length==0)BX.remove(i)}if(t.rows.length==0)BX.remove(t)}return{exec:function(e,t){var n=t.range.commonAncestorContainer;switch(t.actionType){case"insertColumnLeft":case"insertColumnRight":s(n,t.tableNode,t.actionType);break;case"insertRowUpper":case"insertRowLower":d(n,t.tableNode,t.actionType);break;case"insertCellLeft":case"insertCellRight":f(n,t.tableNode,t.actionType);break;case"removeColumn":I(t.range,t.tableNode);break;case"removeRow":S(t.range,t.tableNode);break;case"removeCell":y(t.range,t.tableNode);break;case"removeSelectedCells":T(t.range,t.tableNode);break;case"mergeSelectedCells":N(t.range,t.tableNode);break;case"mergeRightCell":p(t.range,t.tableNode);break;case"mergeBottomCell":v(t.range,t.tableNode);break;case"mergeRow":B(t.range,t.tableNode);break;case"mergeColumn":b(t.range,t.tableNode);break;case"splitHorizontally":C(t.range,t.tableNode);break;case"splitVertically":x(t.range,t.tableNode);break}},state:BX.DoNothing,value:BX.DoNothing,getSelectedCells:a,canBeMerged:m,canBeMergedWithRight:h,canBeMergedWithBottom:g}},GetFormatBbCode:function(){var e=this;return{view:"textarea",exec:function(t,n){var r=n.value,i=n.tag.toUpperCase(),o=i;if(i=="FONT"||i=="COLOR"||i=="SIZE"){i+="="+r}e.editor.textareaView.WrapWith("["+i+"]","[/"+o+"]")},state:BX.DoNothing,value:BX.DoNothing}}};window.BXEditorActions=e})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:64:"/bitrix/js/fileman/html_editor/html-views.min.js?145227744832129";s:6:"source";s:44:"/bitrix/js/fileman/html_editor/html-views.js";s:3:"min";s:48:"/bitrix/js/fileman/html_editor/html-views.min.js";s:3:"map";s:48:"/bitrix/js/fileman/html_editor/html-views.map.js";}"*/
(function(){function e(e,t,i){this.editor=e;this.element=t;this.container=i;this.config=e.config||{};this.isShown=null;this.bbCode=e.bbCode;BX.addCustomEvent(this.editor,"OnClickBefore",BX.proxy(this.OnClick,this))}e.prototype={Focus:function(){if(!document.querySelector||this.element.ownerDocument.querySelector(":focus")===this.element)return;try{this.element.focus()}catch(e){}},Hide:function(){this.isShown=false;this.container.style.display="none"},Show:function(){this.isShown=true;this.container.style.display=""},Disable:function(){this.element.setAttribute("disabled","disabled")},Enable:function(){this.element.removeAttribute("disabled")},OnClick:function(e){},IsShown:function(){return!!this.isShown}};function t(e,t,n){i.superclass.constructor.apply(this,arguments);this.name="textarea";this.InitEventHandlers();if(!this.element.value&&this.editor.config.content)this.SetValue(this.editor.config.content,false)}BX.extend(t,e);t.prototype.Clear=function(){this.element.value=""};t.prototype.GetValue=function(e){var t=this.IsEmpty()?"":this.element.value;if(e){t=this.parent.parse(t)}return t};t.prototype.SetValue=function(e,t,i){if(t){e=this.editor.Parse(e,true,i)}this.editor.dom.pValueInput.value=this.element.value=e};t.prototype.SaveValue=function(){if(this.editor.inited){this.editor.dom.pValueInput.value=this.element.value}};t.prototype.HasPlaceholderSet=function(){return false;var e=supportsPlaceholderAttributeOn(this.element),t=this.element.getAttribute("placeholder")||null,i=this.element.value,n=!i;return e&&n||i===t};t.prototype.IsEmpty=function(){var e=BX.util.trim(this.element.value);return e===""||this.HasPlaceholderSet()};t.prototype.InitEventHandlers=function(){var e=this;BX.bind(this.element,"focus",function(){e.editor.On("OnTextareaFocus");e.isFocused=true});BX.bind(this.element,"blur",function(){e.editor.On("OnTextareaBlur");e.isFocused=false});BX.bind(this.element,"keydown",function(t){e.editor.textareaKeyDownPreventDefault=false;if((t.ctrlKey||t.metaKey)&&!t.altKey&&t.keyCode===e.editor.KEY_CODES["enter"]){e.editor.On("OnCtrlEnter",[t,e.editor.GetViewMode()]);return BX.PreventDefault(t)}e.editor.On("OnTextareaKeydown",[t]);if(e.editor.textareaKeyDownPreventDefault)return BX.PreventDefault(t)});BX.bind(this.element,"keyup",function(t){e.editor.On("OnTextareaKeyup",[t])})};t.prototype.IsFocused=function(){return this.isFocused};t.prototype.ScrollToSelectedText=function(e){};t.prototype.SelectText=function(e){var t=this.element.value,i=t.indexOf(e);if(i!=-1){this.element.focus();this.element.setSelectionRange(i,i+e.length)}};t.prototype.GetTextSelection=function(){var e=false;if(this.element.selectionStart!=undefined){e=this.element.value.substr(this.element.selectionStart,this.element.selectionEnd-this.element.selectionStart)}else if(document.selection&&document.selection.createRange){e=document.selection.createRange().text}else if(window.getSelection){e=window.getSelection();e=e.toString()}return e};t.prototype.WrapWith=function(e,t,i){if(!e)e="";if(!t)t="";if(!i)i="";if(e.length<=0&&t.length<=0&&i.length<=0)return true;var n=!!i,r=this.GetTextSelection(),o=r?"select":n?"after":"in";if(n){i=e+i+t}else if(r){i=e+r+t}else{i=e+t}if(this.element.selectionStart!=undefined){var s=this.element.scrollTop,a=this.element.selectionStart,l=this.element.selectionEnd;this.element.value=this.element.value.substr(0,a)+i+this.element.value.substr(l);if(o=="select"){this.element.selectionStart=a;this.element.selectionEnd=a+i.length}else if(o=="in"){this.element.selectionStart=this.element.selectionEnd=a+e.length}else{this.element.selectionStart=this.element.selectionEnd=a+i.length}this.element.scrollTop=s}else if(document.selection&&document.selection.createRange){var d=document.selection.createRange();var u=d.duplicate();i=i.replace(/\r?\n/g,"\n");d.text=i;d.setEndPoint("StartToStart",u);d.setEndPoint("EndToEnd",u);if(o=="select"){d.collapse(true);i=i.replace(/\r\n/g,"1");d.moveEnd("character",i.length)}else if(o=="in"){d.collapse(false);d.moveEnd("character",e.length);d.collapse(false)}else{d.collapse(false);d.moveEnd("character",i.length);d.collapse(false)}d.select()}else{this.element.value+=i}return true};t.prototype.GetCursorPosition=function(){return this.element.selectionStart};function i(e,t,n){i.superclass.constructor.apply(this,arguments);this.name="wysiwyg";this.caretNode="<br>"}BX.extend(i,e);i.prototype.OnCreateIframe=function(){this.document=this.editor.sandbox.GetDocument();this.element=this.document.body;this.editor.document=this.document;this.textarea=this.editor.dom.textarea;this.isFocused=false;this.InitEventHandlers();window.rangy.init();this.Enable()};i.prototype.Clear=function(){this.element.innerHTML=this.caretNode};i.prototype.GetValue=function(e,t){var i=this.IsEmpty()?"":this.editor.GetInnerHtml(this.element);if(e){i=this.editor.Parse(i,false,t)}return i};i.prototype.SetValue=function(e,t){if(t){e=this.editor.Parse(e)}this.element.innerHTML=e;this.CheckContentLastChild(this.element);this.editor.On("OnIframeSetValue",[e])};i.prototype.Show=function(){this.isShown=true;this.container.style.display="";this.ReInit()};i.prototype.ReInit=function(){this.Disable();this.Enable();this.editor.On("OnIframeReInit")};i.prototype.Hide=function(){this.isShown=false;this.container.style.display="none"};i.prototype.Disable=function(){this.element.removeAttribute("contentEditable")};i.prototype.Enable=function(){this.element.setAttribute("contentEditable","true")};i.prototype.Focus=function(e){if(BX.browser.IsIE()&&this.HasPlaceholderSet()){this.Clear()}if(!document.querySelector||this.element.ownerDocument.querySelector(":focus")!==this.element||!this.IsFocused()){BX.focus(this.element)}if(e&&this.element.lastChild){if(this.element.lastChild.nodeName==="BR"){this.editor.selection.SetBefore(this.element.lastChild)}else{this.editor.selection.SetAfter(this.element.lastChild)}}};i.prototype.SetFocusedFlag=function(e){this.isFocused=e};i.prototype.IsFocused=function(){return this.isFocused};i.prototype.GetTextContent=function(){return this.editor.util.GetTextContent(this.element)};i.prototype.HasPlaceholderSet=function(){return this.GetTextContent()==this.textarea.getAttribute("placeholder")};i.prototype.IsEmpty=function(){if(!document.querySelector)return false;var e=this.element.innerHTML,t="blockquote, ul, ol, img, embed, object, table, iframe, svg, video, audio, button, input, select, textarea";return e===""||e===this.caretNode||this.HasPlaceholderSet()||this.GetTextContent()===""&&!this.element.querySelector(t)};i.prototype._initObjectResizing=function(){var e=["width","height"],t=e.length,i=this.element;this.commands.exec("enableObjectResizing",this.config.allowObjectResizing);if(this.config.allowObjectResizing){if(browser.supportsEvent("resizeend")){dom.observe(i,"resizeend",function(n){var r=n.target||n.srcElement,o=r.style,s=0,a;for(;s<t;s++){a=e[s];if(o[a]){r.setAttribute(a,parseInt(o[a],10));o[a]=""}}redraw(i)})}}else{if(browser.supportsEvent("resizestart")){dom.observe(i,"resizestart",function(e){e.preventDefault()})}}};var n=function(e){if(e.setActive){try{e.setActive()}catch(t){}}else{var i=e.style,n=doc.documentElement.scrollTop||doc.body.scrollTop,r=doc.documentElement.scrollLeft||doc.body.scrollLeft,o={position:i.position,top:i.top,left:i.left,WebkitUserSelect:i.WebkitUserSelect};dom.setStyles({position:"absolute",top:"-99999px",left:"-99999px",WebkitUserSelect:"none"}).on(e);e.focus();dom.setStyles(o).on(e);if(win.scrollTo){win.scrollTo(r,n)}}};i.prototype.InitEventHandlers=function(){var e=this,t=this.editor,i=this.GetValue(),n=this.element,r=!BX.browser.IsOpera()?n:this.editor.sandbox.GetWindow();if(this._eventsInitedObject&&this._eventsInitedObject===r)return;this._eventsInitedObject=r;BX.bind(r,"focus",function(){t.On("OnIframeFocus");e.isFocused=true;if(i!==e.GetValue())BX.onCustomEvent(t,"OnIframeChange")});BX.bind(r,"blur",function(){t.On("OnIframeBlur");e.isFocused=false;setTimeout(function(){i=e.GetValue()},0)});BX.bind(r,"contextmenu",function(e){if(e&&!e.ctrlKey&&!e.shiftKey&&BX.getEventButton(e)&BX.MSRIGHT){t.On("OnIframeContextMenu",[e,e.target||e.srcElement])}});BX.bind(r,"mousedown",function(e){var i=e.target||e.srcElement,n=t.GetBxTag(i);if(t.synchro.IsSyncOn()){t.synchro.StopSync()}if(BX.browser.IsIE10()||BX.browser.IsIE11()){t.phpParser.RedrawSurrogates()}if(i.nodeName=="BODY"||!t.phpParser.CheckParentSurrogate(i)){setTimeout(function(){var e=t.selection.GetRange();if(e&&e.collapsed&&e.startContainer&&e.startContainer==e.endContainer){var i=t.phpParser.CheckParentSurrogate(e.startContainer);if(i){t.selection.SetInvisibleTextAfterNode(i);t.selection.SetInvisibleTextBeforeNode(i)}}},10)}t.selection.SaveRange(false);t.On("OnIframeMouseDown",[e,i,n])});BX.bind(r,"touchstart",function(t){e.Focus()});BX.bind(r,"click",function(e){var i=e.target||e.srcElement;t.On("OnIframeClick",[e,i]);var n=t.selection.GetSelectedNode()});BX.bind(r,"dblclick",function(e){var i=e.target||e.srcElement;t.On("OnIframeDblClick",[e,i])});BX.bind(r,"mouseup",function(e){var i=e.target||e.srcElement;if(!t.synchro.IsSyncOn()){t.synchro.StartSync()}t.On("OnIframeMouseUp",[e,i])});if(BX.browser.IsIOS()&&false){BX.bind(n,"blur",function(){var e=BX.create("INPUT",{props:{type:"text",value:""}},n.ownerDocument),i=document.documentElement.scrollTop||document.body.scrollTop,r=document.documentElement.scrollLeft||document.body.scrollLeft;try{t.selection.InsertNode(e)}catch(o){n.appendChild(e)}BX.focus(e);BX.remove(e);window.scrollTo(r,i)})}BX.bind(n,"dragover",function(){t.On("OnIframeDragOver",arguments)});BX.bind(n,"dragenter",function(){t.On("OnIframeDragEnter",arguments)});BX.bind(n,"dragleave",function(){t.On("OnIframeDragLeave",arguments)});BX.bind(n,"dragexit",function(){t.On("OnIframeDragExit",arguments)});BX.bind(n,"drop",function(){t.On("OnIframeDrop",arguments)});if(BX.browser.IsFirefox()){BX.bind(n,"dragover",function(e){e.preventDefault()});BX.bind(n,"dragenter",function(e){e.preventDefault()})}BX.bind(n,"drop",BX.delegate(this.OnPasteHandler,this));BX.bind(n,"paste",BX.delegate(this.OnPasteHandler,this));BX.bind(n,"keyup",function(i){var n=i.keyCode,r=t.selection.GetSelectedNode(true);e.SetFocusedFlag(true);if(n===t.KEY_CODES["space"]||n===t.KEY_CODES["enter"]){if(n===t.KEY_CODES["enter"]){e.OnEnterHandlerKeyUp(i,n,r)}t.On("OnIframeNewWord")}else{e.OnKeyUpArrowsHandler(i,n)}t.selection.SaveRange();t.On("OnIframeKeyup",[i,n,r])});BX.bind(n,"mousedown",function(i){var n=i.target||i.srcElement;if(!t.util.CheckImageSelectSupport()&&n.nodeName==="IMG"){t.selection.SelectNode(n)}if(!t.util.CheckPreCursorSupport()&&n.nodeName==="PRE"){var r=t.selection.GetSelectedNode(true);if(r&&r!=n){e.FocusPreElement(n,true)}}});BX.bind(n,"keydown",BX.proxy(this.KeyDown,this));var o={IMG:BX.message.SrcTitle+": ",A:BX.message.UrlTitle+": "};BX.bind(n,"mouseover",function(e){var t=e.target||e.srcElement,i=t.nodeName;if(!o[i]){return}if(!t.hasAttribute("title")){t.setAttribute("title",o[i]+(t.getAttribute("href")||t.getAttribute("src")));t.setAttribute("data-bx-clean-attribute","title")}});this.InitClipboardHandler()};i.prototype.KeyDown=function(e){this.SetFocusedFlag(true);this.editor.iframeKeyDownPreventDefault=false;var t=this,i=e.keyCode,n=this.editor.KEY_CODES,r=this.editor.SHORTCUTS[i],o=this.editor.selection.GetSelectedNode(true),s=this.editor.selection.GetRange(),a=this.document.body,l;if((BX.browser.IsIE()||BX.browser.IsIE10()||BX.browser.IsIE11())&&!BX.util.in_array(i,[16,17,18,20,65,144,37,38,39,40])){if(o&&o.nodeName=="BODY"||s.startContainer&&s.startContainer.nodeName=="BODY"||s.startContainer==a.firstChild&&s.endContainer==a.lastChild&&s.startOffset==0&&s.endOffset==a.lastChild.length){BX.addCustomEvent(this.editor,"OnIframeKeyup",BX.proxy(this._IEBodyClearHandler,this))}}if((BX.browser.IsIE()||BX.browser.IsIE10()||BX.browser.IsIE11())&&i==n["backspace"]){BX.addCustomEvent(this.editor,"OnIframeKeyup",BX.proxy(this._IEBodyClearHandlerEx,this))}this.isUserTyping=true;if(this.typingTimeout){this.typingTimeout=clearTimeout(this.typingTimeout)}this.typingTimeout=setTimeout(function(){t.isUserTyping=false},1e3);this.editor.synchro.StartSync(200);this.editor.On("OnIframeKeydown",[e,i,r,o]);if(this.editor.iframeKeyDownPreventDefault)return BX.PreventDefault(e);if((e.ctrlKey||e.metaKey)&&!e.altKey&&r){this.editor.action.Exec(r);return BX.PreventDefault(e)}if(i===n["backspace"]&&s.startOffset==0&&s.startContainer.nodeType==3&&s.startContainer.parentNode.firstChild==s.startContainer&&s.startContainer.parentNode&&s.startContainer.parentNode.nodeName=="BLOCKQUOTE"&&s.startContainer.parentNode.className){s.startContainer.parentNode.className=""}if(i===n["delete"]&&s.collapsed&&s.endContainer.nodeType==3&&s.endOffset==s.endContainer.length){var d=this.editor.util.GetNextNotEmptySibling(s.endContainer);if(d){if(d.nodeName=="BR"){d=this.editor.util.GetNextNotEmptySibling(d)}if(d&&d.nodeName=="BLOCKQUOTE"&&d.className){d.className=""}}}if(o&&o.nodeName==="IMG"&&(i===n["backspace"]||i===n["delete"])){l=o.parentNode;l.removeChild(o);if(l.nodeName==="A"&&!l.firstChild){l.parentNode.removeChild(l)}setTimeout(function(){t.editor.util.Refresh(t.element)},0);BX.PreventDefault(e)}if(s.collapsed&&this.OnKeyDownArrowsHandler(e,i,s)===false){return false}if((e.ctrlKey||e.metaKey)&&!e.altKey&&i===n["enter"]){if(this.IsFocused())this.editor.On("OnIframeBlur");this.editor.On("OnCtrlEnter",[e,this.editor.GetViewMode()]);return BX.PreventDefault(e)}if(BX.browser.IsFirefox()&&o&&(i===n["delete"]||i===n["backspace"])){var u=o.nodeName=="LI"?o:BX.findParent(o,{tag:"LI"},a);if(u&&u.firstChild&&u.firstChild.nodeName=="I"){var c=BX.findParent(u,{tag:"UL"},a);if(c){var f=this.editor.action.actions.insertUnorderedList.getCustomBullitClass(c);if(f){setTimeout(function(){if(c&&u&&u.innerHTML!==""){t.editor.action.actions.insertUnorderedList.checkCustomBullitList(c,f)}},0)}}}}if(!e.shiftKey&&(i===n["enter"]||i===n["backspace"])){return this.OnEnterHandler(e,i,o,s)}if(i===n["pageUp"]||i===n["pageDown"]){this.savedScroll=BX.GetWindowScrollPos(document);BX.addCustomEvent(this.editor,"OnIframeKeyup",BX.proxy(this._RestoreScrollTop,this));setTimeout(BX.proxy(this._RestoreScrollTop,this),0)}};i.prototype._RestoreScrollTop=function(e){if(this.savedScroll){window.scrollTo(this.savedScroll.scrollLeft,this.savedScroll.scrollTop);this.savedScroll=null}BX.removeCustomEvent(this.editor,"OnIframeKeyup",BX.proxy(this._RestoreScrollTop,this))};i.prototype._IEBodyClearHandler=function(e){var t=this.document.body.firstChild;if(e.keyCode==this.editor.KEY_CODES["enter"]&&t.nodeName=="P"&&t!=this.document.body.lastChild){if(t.innerHTML&&t.innerHTML.toLowerCase()=="<br>"){var i=t.nextSibling;this.editor.util.ReplaceWithOwnChildren(t);t=i}}if(t&&t.nodeName=="P"&&t==this.document.body.lastChild){this.editor.util.ReplaceWithOwnChildren(t)}BX.removeCustomEvent(this.editor,"OnIframeKeyup",BX.proxy(this._IEBodyClearHandler,this))};i.prototype._IEBodyClearHandlerEx=function(e){var t=this.document.body.firstChild;if(e.keyCode==this.editor.KEY_CODES["backspace"]&&t&&t.nodeName=="P"&&t==this.document.body.lastChild&&(this.editor.util.IsEmptyNode(t,true,true)||t.innerHTML&&t.innerHTML.toLowerCase()=="<br>")){this.editor.util.ReplaceWithOwnChildren(t)}BX.removeCustomEvent(this.editor,"OnIframeKeyup",BX.proxy(this._IEBodyClearHandlerEx,this))};i.prototype.OnEnterHandler=function(e,t,i,n){if(BX.browser.IsChrome()){this.document.body.style.minHeight=parseInt(this.document.body.style.minHeight)+1+"px";this.document.body.style.minHeight=parseInt(this.document.body.style.minHeight)-1+"px"}if(!i){return}var r=this;function o(e){if(e){if(e.nodeName!=="P"&&e.nodeName!=="DIV"){e=BX.findParent(e,function(e){return e.nodeName==="P"||e.nodeName==="DIV"},r.document.body)}var t=r.editor.util.GetInvisibleTextNode();if(e){e.parentNode.insertBefore(t,e);r.editor.util.ReplaceWithOwnChildren(e);r.editor.selection.SelectNode(t)}}}var s,a,l,d,u,c=["LI","P","H1","H2","H3","H4","H5","H6"],f=["UL","OL","MENU"];if(t===this.editor.KEY_CODES["enter"]&&BX.browser.IsChrome()&&i.nodeName==="LI"&&i.childNodes.length==1&&i.firstChild.nodeName==="BR"){s=BX.findParent(i,function(e){return BX.util.in_array(e.nodeName,f)},r.document.body);l=s.getElementsByTagName("LI");if(i===l[l.length-1]){d=BX.create("BR",{},r.document);this.editor.util.InsertAfter(d,s);this.editor.selection.SetBefore(d);this.editor.Focus();BX.remove(i)}else{var h=s.ownerDocument.createElement(s.nodeName),m=false,p=r.editor.util.GetInvisibleTextNode();for(a=0;a<l.length;a++){if(l[a]==i){m=true;continue}if(m){h.appendChild(l[a])}}if(s.nextSibling){s.parentNode.insertBefore(s.nextSibling,p);p.parentNode.insertBefore(p.nextSibling,h)}else{s.parentNode.appendChild(p);s.parentNode.appendChild(h)}this.editor.selection.SetAfter(p);this.editor.Focus();BX.remove(i)}return BX.PreventDefault(e)}else{if(BX.util.in_array(i.nodeName,c)){u=i}else{u=BX.findParent(i,function(e){return BX.util.in_array(e.nodeName,c)},this.document.body)}if(u){if(u.nodeName==="LI"){if(t===r.editor.KEY_CODES["enter"]&&u&&u.parentNode){var y=r.editor.action.actions.insertUnorderedList.getCustomBullitClass(u.parentNode)}setTimeout(function(){var e=r.editor.selection.GetSelectedNode(true);if(e){s=BX.findParent(e,function(e){return BX.util.in_array(e.nodeName,f)},r.document.body);if(t===r.editor.KEY_CODES["enter"]&&u&&u.parentNode){r.editor.action.actions.insertUnorderedList.checkCustomBullitList(u.parentNode,y,true)}if(!s){o(e)}}},0)}else if(u.nodeName.match(/H[1-6]/)&&t===this.editor.KEY_CODES["enter"]){setTimeout(function(){o(r.editor.selection.GetSelectedNode())},0)}return true}if(t===this.editor.KEY_CODES["enter"]&&!BX.browser.IsFirefox()&&this.editor.action.IsSupported("insertLineBreak")){if(BX.browser.IsIE10()||BX.browser.IsIE11()){this.editor.action.Exec("insertHTML","<br>"+this.editor.INVISIBLE_SPACE)}else if(BX.browser.IsChrome()){this.editor.action.Exec("insertLineBreak");this.editor.action.Exec("insertHTML",this.editor.INVISIBLE_SPACE)}else{this.editor.action.Exec("insertLineBreak")}return BX.PreventDefault(e)}}if((BX.browser.IsChrome()||BX.browser.IsIE10()||BX.browser.IsIE11())&&t==this.editor.KEY_CODES["backspace"]&&n.collapsed){var B=BX.create("SPAN",false,this.document);this.editor.selection.InsertNode(B);var v=B.previousSibling;if(v&&v.nodeType==3&&this.editor.util.IsEmptyNode(v,false,false)){BX.remove(v)}this.editor.selection.SetBefore(B);BX.remove(B)}};i.prototype.OnEnterHandlerKeyUp=function(e,t,i){if(i){var n=this;if(!BX.util.in_array(i.nodeName,this.editor.GetBlockTags())){i=BX.findParent(i,function(e){return BX.util.in_array(e.nodeName,n.editor.GetBlockTags())},this.document.body)}if(i&&BX.util.in_array(i.nodeName,this.editor.GetBlockTags())){var r=BX.util.trim(i.innerHTML).toLowerCase();if(this.editor.util.IsEmptyNode(i,true,true)||r==""||r=="<br>"){i.removeAttribute("class")}}}};i.prototype.OnKeyDownArrowsHandler=function(e,t,i){var n,r,o,s,a=this.editor.KEY_CODES;this.keyDownRange=i;if(t===a["right"]||t===a["down"]){n=i.endContainer;o=n?n.nextSibling:false;r=n?n.parentNode:false;if(n.nodeType==3&&n.length==i.endOffset&&r&&r.nodeName!=="BODY"&&!o&&(this.editor.util.IsBlockElement(r)||this.editor.util.IsBlockNode(r))){this.editor.selection.SetInvisibleTextAfterNode(r,true);return BX.PreventDefault(e)}else if(n.nodeType==3&&this.editor.util.IsEmptyNode(n)&&o&&(this.editor.util.IsBlockElement(o)||this.editor.util.IsBlockNode(o))){BX.remove(n);if(o.firstChild){this.editor.selection.SetBefore(o.firstChild)}else{this.editor.selection.SetAfter(o)}return BX.PreventDefault(e)}}else if(t===a["left"]||t===a["up"]){n=i.startContainer;r=n?n.parentNode:false;s=n?n.previousSibling:false;if(n.nodeType==3&&i.endOffset===0&&r&&r.nodeName!=="BODY"&&!s&&(this.editor.util.IsBlockElement(r)||this.editor.util.IsBlockNode(r))){this.editor.selection.SetInvisibleTextBeforeNode(r);return BX.PreventDefault(e)}else if(n.nodeType==3&&this.editor.util.IsEmptyNode(n)&&s&&(this.editor.util.IsBlockElement(s)||this.editor.util.IsBlockNode(s))){BX.remove(n);if(s.lastChild){this.editor.selection.SetAfter(s.lastChild)}else{this.editor.selection.SetBefore(s)}return BX.PreventDefault(e)}}return true};i.prototype.OnKeyUpArrowsHandler=function(e,t){var i=this,n,r,o,s,a,l=this.editor.selection.GetRange(),d,u,c,f,h,m,p,y,B,v,b,I=this.editor.KEY_CODES;if(t===I["right"]||t===I["down"]){this.editor.selection.GetStructuralTags();if(l.collapsed){d=l.endContainer;h=this.editor.util.IsEmptyNode(d);p=this.editor.selection.CheckLastRange(l);c=d.nextSibling;if(!this.editor.util.CheckPreCursorSupport()){if(d.nodeName==="PRE"){n=d}else if(d.nodeType==3){n=BX.findParent(d,{tag:"PRE"},this.element)}if(n){if(this.keyDownRange){s=this.keyDownRange.endContainer;a=s==n?n:BX.findParent(s,function(e){return e==n},this.element)}i.FocusPreElement(n,false,a?null:"start")}}if(d.nodeType==3&&h&&c){d=c;h=this.editor.util.IsEmptyNode(d)}m=this.editor.util.CheckSurrogateNode(d);if(m){o=d.nextSibling;if(o&&o.nodeType==3&&this.editor.util.IsEmptyNode(o))this.editor.selection._MoveCursorAfterNode(o);else this.editor.selection._MoveCursorAfterNode(d);BX.PreventDefault(e)}else if(d.nodeType==1&&d.nodeName!="BODY"&&!h){if(p){this.editor.selection._MoveCursorAfterNode(d);BX.PreventDefault(e)}}else if(p&&d.nodeType==3&&!h){u=d.parentNode;if(u&&d===u.lastChild&&u.nodeName!="BODY"){this.editor.selection._MoveCursorAfterNode(u)}}else if(d.nodeType==3&&d.parentNode){u=d.parentNode;f=u.previousSibling;if((this.editor.util.IsBlockElement(u)||this.editor.util.IsBlockNode(u))&&f&&f.nodeType==3&&this.editor.util.IsEmptyNode(f)){BX.remove(f)}}}else{y=l.startContainer;B=l.endContainer;v=this.editor.util.CheckSurrogateNode(y);b=this.editor.util.CheckSurrogateNode(B);if(v){r=y.previousSibling;if(r&&r.nodeType==3&&this.editor.util.IsEmptyNode(r))l.setStartBefore(r);else l.setStartBefore(y);this.editor.selection.SetSelection(l)}if(b){o=B.nextSibling;if(o&&o.nodeType==3&&this.editor.util.IsEmptyNode(o))l.setEndAfter(o);else l.setEndAfter(B);this.editor.selection.SetSelection(l)}}}else if(t===I["left"]||t===I["up"]){this.editor.selection.GetStructuralTags();if(l.collapsed){d=l.startContainer;h=this.editor.util.IsEmptyNode(d);p=this.editor.selection.CheckLastRange(l);if(d.nodeType==3&&h&&d.previousSibling){d=d.previousSibling;h=this.editor.util.IsEmptyNode(d)}if(!this.editor.util.CheckPreCursorSupport()){if(d.nodeName==="PRE"){n=d}else if(d.nodeType==3){n=BX.findParent(d,{tag:"PRE"},this.element)}if(n){if(this.keyDownRange){s=this.keyDownRange.startContainer;a=s==n?n:BX.findParent(s,function(e){return e==n},this.element)}i.FocusPreElement(n,false,a?null:"end")}}m=this.editor.util.CheckSurrogateNode(d);if(m){r=d.previousSibling;if(r&&r.nodeType==3&&this.editor.util.IsEmptyNode(r))this.editor.selection._MoveCursorBeforeNode(r);else this.editor.selection._MoveCursorBeforeNode(d);BX.PreventDefault(e)}else if(d.nodeType==1&&d.nodeName!="BODY"&&!h){if(p){this.editor.selection._MoveCursorBeforeNode(d);BX.PreventDefault(e)}}else if(p&&d.nodeType==3&&!h){u=d.parentNode;if(u&&d===u.firstChild&&u.nodeName!="BODY"){this.editor.selection._MoveCursorBeforeNode(u)}}else if(d.nodeType==3&&d.parentNode){u=d.parentNode;f=u.nextSibling;if((this.editor.util.IsBlockElement(u)||this.editor.util.IsBlockNode(u))&&f&&f.nodeType==3&&this.editor.util.IsEmptyNode(f)){BX.remove(f)}}}else{y=l.startContainer;B=l.endContainer;v=this.editor.util.CheckSurrogateNode(y);b=this.editor.util.CheckSurrogateNode(B);if(v){r=y.previousSibling;if(r&&r.nodeType==3&&this.editor.util.IsEmptyNode(r))l.setStartBefore(r);else l.setStartBefore(y);this.editor.selection.SetSelection(l)}if(b){o=B.nextSibling;if(o&&o.nodeType==3&&this.editor.util.IsEmptyNode(o))l.setEndAfter(o);else l.setEndAfter(B);this.SetSelection(l)}}}this.keyDownRange=null};i.prototype.FocusPreElement=function(e,t,i){var n=this;if(this._focusPreElementTimeout)this._focusPreElementTimeout=clearTimeout(this._focusPreElementTimeout);if(t){this._focusPreElementTimeout=setTimeout(function(){n.FocusPreElement(e,false,i)},100);return}BX.focus(e);if(i=="end"&&e.lastChild){this.editor.selection.SetAfter(e.lastChild)}else if(i=="start"&&e.firstChild){this.editor.selection.SetBefore(e.firstChild)}};i.prototype.OnPasteHandler=function(e){if(!this.editor.skipPasteHandler){this.editor.skipPasteHandler=true;var t=document.documentElement.scrollTop||document.body.scrollTop,i=document.documentElement.scrollLeft||document.body.scrollLeft,n=this,r=[],o,s,a,l;function d(e){if(e&&e.setAttribute){e.setAttribute("data-bx-paste-flag","Y")}}function u(e){return e.nodeType==1?e:BX.findParent(e,function(e){return e.nodeType==1})}o=this.document.body;if(o){l=o.querySelectorAll("*");for(s=0;s<l.length;s++){if(l[s].nodeType==1&&l[s].nodeName!="BODY"&&l[s].nodeName!="HEAD"){r.push(l[s])}}for(s=0;s<o.parentNode.childNodes.length;s++){a=o.parentNode.childNodes[s];if(a.nodeType==1&&a.nodeName!="BODY"&&a.nodeName!="HEAD"){r.push(a)}}}for(s=0;s<r.length;s++){d(r[s])}var c=this.editor.synchro.IsSyncOn();if(c){this.editor.synchro.StopSync()}setTimeout(function(){n.editor.SetCursorNode();n.editor.pasteHandleMode=true;n.editor.bbParseContentMode=true;n.editor.synchro.lastIframeValue=false;n.editor.synchro.FromIframeToTextarea(true,true);n.editor.pasteHandleMode=false;n.editor.bbParseContentMode=false;n.editor.synchro.lastTextareaValue=false;n.editor.synchro.FromTextareaToIframe(true);n.editor.RestoreCursor();n.editor.On("OnIframePaste");n.editor.On("OnIframeNewWord");n.editor.skipPasteHandler=false;if(c){n.editor.synchro.StartSync()}if(window.scrollTo){window.scrollTo(i,t)}},10)}};i.prototype.InitAutoLinking=function(){var e=this,t=this.editor,i=t.action.IsSupportedByBrowser("autoUrlDetect"),n=BX.browser.IsIE()||BX.browser.IsIE9()||BX.browser.IsIE10();if(i)t.action.Exec("autoUrlDetect",false);if(t.config.autoLink===false)return;var r={CODE:1,PRE:1,A:1,SCRIPT:1,HEAD:1,TITLE:1,STYLE:1},o=/(((?:https?|ftp):\/\/|www\.)[^\s<]{3,500})/gi,s=/[\.a-z0-9_\-]+@[\.a-z0-9_\-]+\.[\.a-z0-9_\-]+/gi,a=100,l={")":"(","]":"[","}":"{"};this.editor.autolinkUrlRegExp=o;this.editor.autolinkEmailRegExp=s;function d(e){if(e&&!r[e.nodeName]){var t=BX.findParent(e,function(e){return!!r[e.nodeName]},e.ownerDocument.body);if(t)return e;if(e===e.ownerDocument.documentElement)e=e.ownerDocument.body;return h(e)}}function u(e){return e.replace(o,function(e,t){var i=(t.match(/([^\w\u0430-\u0456\u0451\/\-](,?))$/i)||[])[1]||"",n=l[i];t=t.replace(/([^\w\u0430-\u0456\u0451\/\-](,?))$/i,"");if(t.split(n).length>t.split(i).length){t=t+i;i=""}var r=t,o=t;if(t.length>a)o=o.substr(0,a)+"...";if(r.substr(0,4)==="www.")r="http://"+r;return'<a href="'+r+'">'+o+"</a>"+i})}function c(e){return e.replace(s,function(e){var t=(e.match(/([^\w\/\-](,?))$/i)||[])[1]||"",i=l[t];e=e.replace(/([^\w\/\-](,?))$/i,"");if(e.split(i).length>e.split(t).length){e=e+t;t=""}var n="mailto:"+e;return'<a href="'+n+'">'+e+"</a>"+t})}function f(e){var t=e._bx_autolink_temp_div;if(!t)t=e._bx_autolink_temp_div=e.createElement("div");return t}function h(e){var t,i,n;if(e&&!r[e.nodeName]){if(e.nodeType===3&&e.data.match(o)&&e.parentNode){i=e.parentNode;n=f(i.ownerDocument);n.innerHTML="<span></span>"+u(e.data);n.removeChild(n.firstChild);while(n.firstChild)i.insertBefore(n.firstChild,e);i.removeChild(e)}else if(e.nodeType===3&&e.data.match(s)&&e.parentNode){i=e.parentNode;n=f(i.ownerDocument);n.innerHTML="<span></span>"+c(e.data);n.removeChild(n.firstChild);while(n.firstChild)i.insertBefore(n.firstChild,e);i.removeChild(e)}else if(e.nodeType===1){var a=e.childNodes,l;for(l=0;l<a.length;l++)h(a[l]);t=e}}return t}if(!n||n&&i){BX.addCustomEvent(t,"OnIframeNewWord",function(){try{t.selection.ExecuteAndRestore(function(e,t){d(t.parentNode)})}catch(e){}});BX.addCustomEvent(t,"OnSubmit",function(){try{d(t.GetIframeDoc().body)}catch(e){}})}var m=t.sandbox.GetDocument().getElementsByTagName("a"),p=function(e){var i=BX.util.trim(t.util.GetTextContent(e));if(i.substr(0,4)==="www.")i="http://"+i;return i};BX.addCustomEvent(t,"OnIframeKeydown",function(e,t,i,n){if(m.length>0&&n){var r=BX.findParent(n,{tag:"A"},n.ownerDocument.body);if(r){var s=p(r);setTimeout(function(){var e=p(r);if(e===s)return;if(e.match(o))r.setAttribute("href",e)},0)}}})};i.prototype.IsUserTypingNow=function(e){return this.isFocused&&this.isShown&&this.isUserTyping};i.prototype.CheckContentLastChild=function(e){if(!e){e=this.element}var t=e.lastChild;if(t&&(this.editor.util.IsEmptyNode(t,true)&&this.editor.util.IsBlockNode(t.previousSibling)||this.editor.phpParser.IsSurrogate(t))){e.appendChild(BX.create("BR",{},e.ownerDocument));e.appendChild(this.editor.util.GetInvisibleTextNode())}};i.prototype.InitClipboardHandler=function(){var e=this;BX.bind(this.element,"paste",function(t){var i=t.clipboardData;if(i&&i.items){var n=i.items[0];if(n&&n.type.indexOf("image/")>-1){var r=n.getAsFile();if(r){var o=new FileReader;o.readAsDataURL(r);o.onload=function(t){var i=new Image;i.src=t.target.result;e.element.appendChild(i);e.HandleImageDataUri(i)}}}}})};i.prototype.HandleImageDataUri=function(e){this.editor.On("OnImageDataUriHandle",[this,{src:e.src,title:e.title||""},BX.proxy(this.HandleImageDataUriCallback,this)])};i.prototype.HandleImageDataUriCallback=function(e){};function r(e,t,i){this.INTERVAL=500;this.editor=e;this.textareaView=t;this.iframeView=i;this.lastFocused="wysiwyg";this.InitEventHandlers()}r.prototype={FromIframeToTextarea:function(e,t){var i;if(this.editor.bbCode){i=this.iframeView.GetValue(this.editor.bbParseContentMode,false);i=BX.util.trim(i);if(i!==this.lastIframeValue){var n=this.editor.bbParser.Unparse(i);this.textareaView.SetValue(n,false,t||this.editor.bbParseContentMode);this.editor.On("OnContentChanged",[n||"",i||""]);this.lastIframeValue=i}}else{i=this.iframeView.GetValue();i=BX.util.trim(i);if(i!==this.lastIframeValue){this.textareaView.SetValue(i,true,t);this.editor.On("OnContentChanged",[this.textareaView.GetValue()||"",i||""]);this.lastIframeValue=i}}},FromTextareaToIframe:function(e){var t=this.textareaView.GetValue();if(t!==this.lastTextareaValue){if(t){if(this.editor.bbCode){var i=this.editor.bbParser.Parse(t);i=i.replace(/\u2060/gi,'<span id="bx-cursor-node"> </span>');this.iframeView.SetValue(i,e)}else{t=t.replace(/\u2060/gi,'<span id="bx-cursor-node"> </span>');this.iframeView.SetValue(t,e)}}else{this.iframeView.Clear()}this.lastTextareaValue=t;this.editor.On("OnContentChanged",[t||"",this.iframeView.GetValue()||""])}},FullSyncFromIframe:function(){this.lastIframeValue=false;this.FromIframeToTextarea(true,true);this.lastTextareaValue=false;this.FromTextareaToIframe(true)},Sync:function(){var e=true;var t=this.editor.currentViewName;if(t==="split"){if(this.GetSplitMode()==="code"){this.FromTextareaToIframe(e)}else{this.FromIframeToTextarea(e)}}else if(t==="code"){this.FromTextareaToIframe(e)}else{this.FromIframeToTextarea(e)}},GetSplitMode:function(){var e=false;if(this.editor.currentViewName=="split"){if(this.editor.iframeView.IsFocused()){e="wysiwyg"}else if(this.editor.textareaView.IsFocused()){e="code"}else{e=this.lastFocused}}return e},InitEventHandlers:function(){var e=this;BX.addCustomEvent(this.editor,"OnTextareaFocus",function(){e.lastFocused="code";e.StartSync()});BX.addCustomEvent(this.editor,"OnIframeFocus",function(){e.lastFocused="wysiwyg";e.StartSync()});BX.addCustomEvent(this.editor,"OnTextareaBlur",BX.delegate(this.StopSync,this));BX.addCustomEvent(this.editor,"OnIframeBlur",BX.delegate(this.StopSync,this))},StartSync:function(e){var t=this;if(this.interval){this.interval=clearTimeout(this.interval)}this.delay=e||this.INTERVAL;function i(){t.delay=t.INTERVAL;t.Sync();t.interval=setTimeout(i,t.delay)}this.interval=setTimeout(i,t.delay)},StopSync:function(){if(this.interval){this.interval=clearTimeout(this.interval)}},IsSyncOn:function(){return!!this.interval},OnIframeMousedown:function(e,t,i){},IsFocusedOnTextarea:function(){var e=this.editor.currentViewName;return e==="code"||e==="split"&&this.GetSplitMode()==="code"}};window.BXEditorTextareaView=t;

window.BXEditorIframeView=i;window.BXEditorViewsSynchro=r})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:65:"/bitrix/js/fileman/html_editor/html-parser.min.js?145227744850418";s:6:"source";s:45:"/bitrix/js/fileman/html_editor/html-parser.js";s:3:"min";s:49:"/bitrix/js/fileman/html_editor/html-parser.min.js";s:3:"map";s:49:"/bitrix/js/fileman/html_editor/html-parser.map.js";}"*/
(function(){function e(e){this.editor=e;this.specialParsers={};this.DEFAULT_NODE_NAME="span",this.WHITE_SPACE_REG_EXP=/\s+/,this.defaultRules={tags:{},classes:{}};this.convertedBxNodes=[];this.rules={};this.FIRST_LETTER_CLASS="bxe-first-letter";this.FIRST_LETTER_CLASS_CHROME="bxe-first-letter-chrome";this.FIRST_LETTER_SPAN="bxe-first-letter-s"}e.prototype={Parse:function(e,t,r,i,s){if(!r){r=document}this.convertedBxNodes=[];var a=r.createDocumentFragment(),n=this.GetAsDomElement(e,r),o,l,d;this.SetParseBxMode(s);while(n.firstChild){d=n.firstChild;n.removeChild(d);o=this.Convert(d,i,s,o);if(o){l=!s&&this.CheckBlockNode(o);if(l){}a.appendChild(o);if(l){a.appendChild(this.editor.util.GetInvisibleTextNode())}}}n.innerHTML="";n.appendChild(a);e=this.editor.GetInnerHtml(n);e=this.RegexpContentParse(e,s);return e},SetParseBxMode:function(e){this.bParseBx=!!e},CodeParse:function(e){return e},GetAsDomElement:function(e,t){if(!t)t=document;var r=t.createElement("DIV");if(typeof e==="object"&&e.nodeType){r.appendChild(e)}else if(this.editor.util.CheckHTML5Support()){r.innerHTML=e}else if(this.editor.util.CheckHTML5FullSupport()){r.style.display="none";t.body.appendChild(r);try{r.innerHTML=e}catch(i){}t.body.removeChild(r)}return r},Convert:function(e,t,r,i){var s=false,a=e.nodeType,n=e.childNodes,o,l,d,u;if(a==1){if(e.nodeName=="IMG"){if(!e.getAttribute("data-bx-orig-src"))e.setAttribute("data-bx-orig-src",e.getAttribute("src"));else e.setAttribute("src",e.getAttribute("data-bx-orig-src"))}if(this.editor.pasteHandleMode&&(r||this.editor.bbParseContentMode)){if(e.id=="bx-cursor-node"){return e.ownerDocument.createTextNode(this.editor.INVISIBLE_CURSOR)}s=!e.getAttribute("data-bx-paste-flag");if(e&&e.id){u=this.editor.GetBxTag(e.id);if(u.tag){s=false}}if(s){e=this.CleanNodeAfterPaste(e,i);if(!e){return null}n=e.childNodes;a=e.nodeType}e.removeAttribute("data-bx-paste-flag")}else{if(e.id=="bx-cursor-node"){return e.cloneNode(true)}}if(a==1){if(!e.__bxparsed){if(this.IsAnchor(e)&&!e.getAttribute("data-bx-replace_with_children")){o=e.cloneNode(true);o.innerHTML="";l=null;for(d=0;d<n.length;d++){l=this.Convert(n[d],t,r,l);if(l){o.appendChild(l)}}var f={};for(d=0;d<o.attributes.length;d++){if(o.attributes[d].name!=="name")f[o.attributes[d].name]=o.attributes[d].value}e=this.editor.phpParser.GetSurrogateNode("anchor",BX.message("BXEdAnchor")+": #"+o.name,null,{html:o.innerHTML,name:o.name,attributes:f})}else if(this.IsPrintBreak(e)){e=this.GetPrintBreakSurrogate(e)}if(e&&e.id){u=this.editor.GetBxTag(e.id);if(u.tag){e.__bxparsed=1;if(this.bParseBx){o=e.ownerDocument.createTextNode("~"+u.id+"~");this.convertedBxNodes.push(u)}else{o=e.cloneNode(true)}return o}}if(!o&&e.nodeType){o=this.ConvertElement(e,r)}}}}else if(a==3){o=this.HandleText(e)}if(!o){return null}for(d=0;d<n.length;d++){l=this.Convert(n[d],t,r);if(l){o.appendChild(l)}}if(o.nodeType==1){if(o.style&&BX.util.trim(o.style.cssText)==""&&o.removeAttribute){o.removeAttribute("style")}if(this.editor.config.cleanEmptySpans&&t&&o.childNodes.length<=1&&o.nodeName.toLowerCase()===this.DEFAULT_NODE_NAME&&!o.attributes.length){return o.firstChild}}return o},ConvertElement:function(e,t){var r,i,s,a=this.editor.GetParseRules().tags,n=e.nodeName.toLowerCase(),o=e.scopeName;if(e.__bxparsed){return null}e.__bxparsed=1;if(e.className==="bx-editor-temp"){return null}if(o&&o!="HTML"){n=o+":"+n}if("outerHTML"in e&&!this.editor.util.AutoCloseTagSupported()&&e.nodeName==="P"&&e.outerHTML.slice(-4).toLowerCase()!=="</p>"){n="div"}if(this.editor.util.FirstLetterSupported()&&e.className&&false){if(e.className==this.FIRST_LETTER_CLASS&&!this.bParseBx){this.HandleFirstLetterNode(e)}else if(e.className==this.FIRST_LETTER_CLASS_CHROME&&this.bParseBx){this.HandleFirstLetterNodeBack(e)}}if(n=="table"&&!this.bParseBx){var l=parseInt(e.getAttribute("border"),10);if(!l){e.removeAttribute("border");e.setAttribute("data-bx-no-border","Y")}}if(n in a){r=a[n];if(!r||r.remove){return null}if(r.clean_empty&&(e.innerHTML===""||e.innerHTML===this.editor.INVISIBLE_SPACE)&&(!e.className||e.className=="")&&(!this.editor.lastCreatedId||this.editor.lastCreatedId!=e.getAttribute("data-bx-last-created-id"))){return null}r=typeof r==="string"?{rename_tag:r}:r;s=e.getAttribute("data-bx-new-rule");if(s){r[s]=e.getAttribute("data-bx-"+s)}}else if(e.firstChild){r={rename_tag:this.DEFAULT_NODE_NAME}}else{return null}if(r.replace_with_children){i=e.ownerDocument.createDocumentFragment()}else{i=e.ownerDocument.createElement(r.rename_tag||n);this.HandleAttributes(e,i,r,t)}if(s){r[s]=null;delete r[s]}e=null;return i},CleanNodeAfterPaste:function(e,t){var r,i,s=e.nodeName,a=e.innerHTML,n=BX.util.trim(a),o={align:1,alt:1,bgcolor:1,border:1,cellpadding:1,cellspacing:1,color:1,colspan:1,height:1,href:1,rowspan:1,size:1,span:1,src:1,style:1,target:1,title:1,type:1,value:1,width:1},l={B:1,STRONG:1,I:1,EM:1,U:1,DEL:1,S:1,STRIKE:1},d={A:1,SPAN:1,B:1,STRONG:1,I:1,EM:1,U:1,DEL:1,S:1,STRIKE:1,H1:1,H2:1,H3:1,H4:1,H5:1,H6:1,ABBR:1,TIME:1,FIGURE:1,FIGCAPTION:1};if(s=="IFRAME"){return null}if(e.style.display=="none"||e.style.visibility=="hidden"){return null}if(d[s]&&a==""){return null}var u=e.getAttribute("data-bx-clean-attribute");if(u){e.removeAttribute(u);e.removeAttribute("data-bx-clean-attribute")}if(s=="IMG"){var f=e.getAttribute("alt");if(f===""){e.removeAttribute("alt")}else if(typeof f=="string"&&f!==""&&f.indexOf("://")!==-1){this.CheckAltImage(e)}e.removeAttribute("class");this.CleanNodeCss(e);return e}if(s=="A"&&(n==""||n=="&nbsp;")){return null}if(s=="A"){}e.removeAttribute("class");e.removeAttribute("id");i=0;while(i<e.attributes.length){r=e.attributes[i].name;if(!o[r]||e.attributes[i].value==""){e.removeAttribute(r)}else{i++}}if(s=="DIV"||e.style.display=="block"||s=="FORM"){if(!e.lastChild||e.lastChild&&e.lastChild.nodeName!="BR"){e.appendChild(e.ownerDocument.createElement("BR")).setAttribute("data-bx-paste-flag","Y")}if(t&&typeof t=="object"&&t.nodeType==3&&e.firstChild){e.insertBefore(e.ownerDocument.createElement("BR"),e.firstChild).setAttribute("data-bx-paste-flag","Y")}e.setAttribute("data-bx-new-rule","replace_with_children");e.setAttribute("data-bx-replace_with_children","1")}if(s=="B"&&e.style.fontWeight=="normal"){e.setAttribute("data-bx-new-rule","replace_with_children");e.setAttribute("data-bx-replace_with_children","1")}if(l[s]&&!this.editor.config.pasteSetDecor){e.setAttribute("data-bx-new-rule","replace_with_children");e.setAttribute("data-bx-replace_with_children","1")}if(this.IsAnchor(e)&&(e.name==""||BX.util.trim(e.name==""))){e.setAttribute("data-bx-new-rule","replace_with_children");e.setAttribute("data-bx-replace_with_children","1")}this.CleanNodeCss(e);if(s=="SPAN"&&e.style.cssText==""){e.setAttribute("data-bx-new-rule","replace_with_children");e.setAttribute("data-bx-replace_with_children","1")}if((s=="P"||s=="SPAN"||s=="FONT")&&BX.util.trim(e.innerHTML)=="&nbsp;"){e.innerHTML=" "}return e},CleanNodeCss:function(e){var t,r,i,s,a,n=e.nodeName,o={height:["auto"],width:["auto"]};if(!this.editor.config.pasteSetColors){o["color"]=["#000000","#000","black"];o["background-color"]=["transparent","#fff","#ffffff","white"];o["background"]=1}if(!this.editor.config.pasteSetBorders){o["border-collapse"]=1;o["border-color"]=["transparent","#fff","#ffffff","white"];o["border-style"]=["none","hidden"];o["border-top"]=["0px","0"];o["border-right"]=["0px","0"];o["border-bottom"]=["0px","0"];o["border-left"]=["0px","0"];o["border-top-color"]=["transparent","#fff","#ffffff","white"];o["border-right-color"]=["transparent","#fff","#ffffff","white"];o["border-bottom-color"]=["transparent","#fff","#ffffff","white"];o["border-left-color"]=["transparent","#fff","#ffffff","white"];o["border-top-style"]=["none","hidden"];o["border-right-style"]=["none","hidden"];o["border-bottom-style"]=["none","hidden"];o["border-left-style"]=["none","hidden"];o["border-top-width"]=["0px","0"];o["border-right-width"]=["0px","0"];o["border-bottom-width"]=["0px","0"];o["border-left-width"]=["0px","0"];o["border-width"]=["0px","0"];o["border"]=["0px","0"]}if(!this.editor.config.pasteSetDecor){o["font-style"]=["normal"];o["font-weight"]=["normal"];o["text-decoration"]=["none"]}if(e.style&&BX.util.trim(e.style.cssText)!=""&&n!=="BR"){t=[];for(a in e.style){if(e.style.hasOwnProperty(a)){if(parseInt(a).toString()===a){i=e.style[a];s=e.style.getPropertyValue(i)}else{i=a;s=e.style.getPropertyValue(i)}if(s===null)continue;if(!o[i]||s.match(/^-(moz|webkit|ms|o)/gi)||s=="inherit"||s=="initial"||i=="color"&&n=="A"||(n=="SPAN"||n=="P")&&(i=="width"||i=="height")||typeof o[i]=="object"&&BX.util.in_array(s.toLowerCase(),o[i])){continue}if(i.indexOf("color")!==-1){s=this.editor.util.RgbToHex(s);if(typeof o[i]=="object"&&BX.util.in_array(s.toLowerCase(),o[i])||s=="transparent"){continue}}if(i.indexOf("border")!==-1&&s.indexOf("none")!==-1){continue}t.push({name:i,value:s})}}e.removeAttribute("style");if(t.length>0){for(r=0;r<t.length;r++){e.style[t[r].name]=t[r].value}}}else{e.removeAttribute("style")}},CheckAltImage:function(e){var t=this.editor.GetIframeDoc();function r(e){var r,i=t.getElementsByTagName("IMG");for(r=0;r<i.length;r++){if(i[r].src===e){return i[r]}}}function i(){if(e.src===e.alt&&e.getAttribute("data-bx-orig-src")!==e.src){e.setAttribute("data-bx-orig-src",e.getAttribute("src"))}BX.unbind(e,"load",i);BX.unbind(e,"error",s)}function s(){var t=e.getAttribute("alt"),a=r(e.src);if(!a){BX.unbind(e,"load",i);BX.unbind(e,"error",s);return}if(e.getAttribute("src")!==e.alt){a.setAttribute("src",t)}else{a.setAttribute("src",e.getAttribute("data-bx-orig-src"));BX.unbind(e,"load",i);BX.unbind(e,"error",s)}}BX.bind(e,"load",i);BX.bind(e,"error",s)},HandleText:function(e){var t=e.data;if(this.editor.pasteHandleMode&&t.indexOf("EndFragment:")!==-1){t=t.replace(/Version:\d\.\d(?:\s|\S)*?StartHTML:\d+(?:\s|\S)*?EndHTML:\d+(?:\s|\S)*?StartFragment:\d+(?:\s|\S)*?EndFragment:\d+(?:\s|\n|\t|\r)*/g,"")}return e.ownerDocument.createTextNode(t)},HandleAttributes:function(e,t,r,i){var s={},a=r.set_class,n=r.add_class,o=r.add_css,l=r.set_attributes,d=r.check_attributes,u=r.clear_attributes,f=this.editor.GetParseRules().classes,h=0,c,g={},p,m=[],b=[],T=[],B=[],P,v,S,x,C,A,y,I;if(d){for(A in d){I=this.GetCheckAttributeHandler(d[A]);if(!I)continue;y=I(this.GetAttributeEx(e,A));if(typeof y==="string"&&y!=="")s[A]=y}}var E=e.getAttribute("data-bx-clean-attribute");if(E){e.removeAttribute(E);e.removeAttribute("data-bx-clean-attribute")}if(!u){for(h=0;h<e.attributes.length;h++){C=e.attributes[h];if(i){if(C.name.substr(0,15)=="data-bx-app-ex-"){c=C.name.substr(15);s[c]=e.getAttribute(C.name);g[c]=true}if(g[C.name]){continue}}if(C.name.substr(0,8)=="data-bx-"&&C.name!="data-bx-noindex"&&this.bParseBx){continue}s[C.name]=this.GetAttributeEx(e,C.name)}}if(a)m.push(a);if(o){for(p in o){if(o.hasOwnProperty(p))t.style[p]=o[p]}}B=e.getAttribute("class");if(B)m=m.concat(B.split(this.WHITE_SPACE_REG_EXP));P=m.length;for(;h<P;h++){S=m[h];if(f[S])b.push(S)}if(T.length)s["class"]=T.join(" ");for(A in s){try{t.setAttribute(A,s[A])}catch(N){}}if(s.src){if(typeof s.width!=="undefined")t.setAttribute("width",s.width);if(typeof s.height!=="undefined")t.setAttribute("height",s.height)}},GetAttributeEx:function(e,t){t=t.toLowerCase();var r,i=e.nodeName;if(i=="IMG"&&t=="src"&&this.IsLoadedImage(e)===true){r=e.getAttribute("src")}else if(!this.editor.util.CheckGetAttributeTruth()&&"outerHTML"in e){var s=e.outerHTML.toLowerCase(),a=s.indexOf(" "+t+"=")!=-1;r=a?e.getAttribute(t):null}else{r=e.getAttribute(t)}return r},IsLoadedImage:function(e){try{return e.complete&&!e.mozMatchesSelector(":-moz-broken")}catch(t){if(e.complete&&e.readyState==="complete")return true}return false},GetCheckAttributeHandler:function(e){var t=this.GetCheckAttributeHandlers();return t[e]},GetCheckAttributeHandlers:function(){return{url:function(e){return e},alt:function(e){if(!e){return""}return e.replace(/[^ a-z0-9_\-]/gi,"")},numbers:function(e){e=(e||"").replace(/\D/g,"");return e||null}}},HandleBitrixNode:function(e){return e},RegexpContentParse:function(e,t){if(e.indexOf("rgb")!==-1){e=e.replace(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+))?\)/gi,function(e,t,r,i,s){function a(e){return("0"+parseInt(e).toString(16)).slice(-2)}return"#"+a(t)+a(r)+a(i)})}if(t&&e.indexOf("data-bx-noindex")!==-1){e=e.replace(/(<a[^(<a)]*?)data\-bx\-noindex="Y"([\s\S]*?>[\s\S]*?\/a>)/gi,function(e,t,r){return"<!--noindex-->"+t+r+"<!--/noindex-->"})}if(t){e=e.replace(/\uFEFF/gi,"")}else{e=e.replace(/\uFEFF+/gi,this.editor.INVISIBLE_SPACE)}if(t&&e.indexOf("#BXAPP")!==-1){var r=this;e=e.replace(/#BXAPP(\d+)#/g,function(e,t){t=parseInt(t,10);return r.editor.phpParser.AdvancedPhpGetFragmentByIndex(t,true)})}return e},IsAnchor:function(e){return e.nodeName=="A"&&!e.href},IsPrintBreak:function(e){return e.style.pageBreakAfter=="always"},GetPrintBreakSurrogate:function(e){var t=this.editor.GetIframeDoc(),r=this.editor.SetBxTag(false,{tag:"printbreak",params:{innerHTML:BX.util.trim(e.innerHTML)},name:BX.message("BXEdPrintBreakName"),title:BX.message("BXEdPrintBreakTitle")});return BX.create("IMG",{props:{src:this.editor.EMPTY_IMAGE_SRC,id:r,className:"bxhtmled-printbreak",title:BX.message("BXEdPrintBreakTitle")}},t)},CheckBlockNode:function(e){return this.editor.phpParser.IsSurrogate(e)||e.nodeType==1&&(e.style.display=="block"||e.style.display=="inline-block"||e.nodeName=="BLOCKQUOTE"||e.nodeName=="DIV")},HandleFirstLetterNode:function(e){e.className=this.FIRST_LETTER_CLASS_CHROME;var t=this._GetFlTextNode(e),r,i;if(t){r=BX.util.trim(this.editor.util.GetTextContent(t));i=BX.create("SPAN",{props:{className:this.FIRST_LETTER_SPAN},text:r.substr(0,1)},e.ownerDocument);this.editor.util.SetTextContent(t,r.substr(1));t.parentNode.insertBefore(i,t)}},HandleFirstLetterNodeBack:function(e){e.className=this.FIRST_LETTER_CLASS;var t=this._GetFlSpan(e);if(t){this.editor.util.ReplaceWithOwnChildren(t)}},_GetFlSpan:function(e){return BX.findChild(e,{className:this.FIRST_LETTER_SPAN},1)},_GetFlTextNode:function(e){if(e.innerHTML==""||!e.firstChild)return null}};function t(e){this.PHP_PATTERN="#BXPHP_IND#";this.editor=e;this.allowed={php:this.editor.allowPhp||this.editor.lpa,javascript:true,style:true,htmlcomment:true,iframe:true,video:true,object:true};this.bUseAPP=true;this.APPConfig={arTags_before:["tbody","thead","tfoot","tr","td","th"],arTags_after:["tbody","thead","tfoot","tr","td","th"],arTags:{a:["href","title","class","style"],img:["src","alt","class","style","width","height"]}};this.customParsers=[];this.arScripts={};this.arJavascripts={};this.arHtmlComments={};this.arIframes={};this.arVideos={};this.arStyles={};this.arObjects={};this.surrClass="bxhtmled-surrogate";this.surrogateTags={component:1,php:1,javascript:1,style:1,htmlcomment:1,anchor:1,iframe:1,video:1,object:1};BX.addCustomEvent(this.editor,"OnIframeMouseDown",BX.proxy(this.OnSurrogateMousedown,this));BX.addCustomEvent(this.editor,"OnIframeDblClick",BX.proxy(this.OnSurrogateDblClick,this));BX.addCustomEvent(this.editor,"OnIframeKeydown",BX.proxy(this.OnSurrogateKeydown,this));BX.addCustomEvent(this.editor,"OnAfterCommandExec",BX.proxy(this.RenewSurrogates,this))}t.prototype={ParsePhp:function(e){var t=this;if(this.IsAllowed("php")){e=this.ReplacePhpBySymCode(e)}else{e=this.CleanPhp(e)}e=this.CustomContentParse(e);e=this.ReplaceJavascriptBySymCode(e);e=this.ReplaceHtmlCommentsBySymCode(e);e=this.ReplaceIframeBySymCode(e);e=this.ReplaceStyleBySymCode(e);e=this.ReplaceObjectBySymCode(e);e=this.ParseBreak(e);e=this.AdvancedPhpParse(e);e=this.ParseSymCode(e);if(this.editor.lpa){e=e.replace(/#PHP(\d+)#/g,function(e){return t.GetSurrogateHTML("php_protected",BX.message("BXEdPhpCode")+" *",BX.message("BXEdPhpCodeProtected"),{value:e})})}return e},ReplacePhpBySymCode:function(e,t){var r=[],i=0,s,a,n,o,l,d,u,f=0;t=t===true;while((i=e.indexOf("<?",i))>=0){f=0;s=i+2;a=false;n=false;while(s<e.length-1){s++;o=e.substr(s,1);if(!n){if(o=="/"&&s+1<e.length){l=e.indexOf("?>",s);if(l==-1){i=e.length;break}l+=2;d=0;if(e.substr(s+1,1)=="*"&&(d=e.indexOf("*/",s+2))>=0){d+=2}else if(e.substr(s+1,1)=="/"&&(d=e.indexOf("\n",s+2))>=0){d+=1}if(d>0){if(d>l&&e.substr(s+1,1)!="*"){r.push([i,l,e.substr(i,l-i)]);i=l;break}else{s=d-1}}continue}if(o=="?"&&s+1<e.length&&e.substr(s+1,1)==">"){s=s+2;r.push([i,s,e.substr(i,s-i)]);i=s+1;break}}if(n&&o=="\\"){a=true;continue}if(o=='"'||o=="'"){if(n){if(!a&&u==o)n=false}else{n=true;u=o}}a=false}if(s>=e.length)break;i=s}this.arScripts={};if(r.length>0){var h="",c=0,g;if(t){for(s=0;s<r.length;s++){g=r[s];h+=e.substr(c,g[0]-c);c=g[1]}}else{for(s=0;s<r.length;s++){g=r[s];h+=e.substr(c,g[0]-c)+this.SavePhpCode(g[2],s);c=g[1]}}e=h+e.substr(c)}return e},CleanPhp:function(e){return this.ReplacePhpBySymCode(e,true)},ReplaceJavascriptBySymCode:function(e){this.arJavascripts={};var t=this,r=0;e=e.replace(/<script[\s\S]*?\/script>/gi,function(e){t.arJavascripts[r]=e;var i=t.GetPattern(r,false,"javascript");r++;return i});return e},ReplaceHtmlCommentsBySymCode:function(e){this.arHtmlComments={};var t=this,r=0;e=e.replace(/(<!--noindex-->)(?:[\s|\n|\r|\t]*?)<a([\s\S]*?)\/a>(?:[\s|\n|\r|\t]*?)(<!--\/noindex-->)/gi,function(e,t,r,i){return'<a data-bx-noindex="Y"'+r+"/a>"});e=e.replace(/<!--[\s\S]*?-->/gi,function(e){t.arHtmlComments[r]=e;return t.GetPattern(r++,false,"html_comment")});return e},ReplaceIframeBySymCode:function(e){this.arIframes={};var t=this,r=0;e=e.replace(/<iframe([\s\S]*?)\/iframe>/gi,function(e,i){var s=t.CheckForVideo(i);if(s){t.arVideos[r]={html:e,provider:s.provider||false,src:s.src||false};return t.GetPattern(r++,false,"video")}else{t.arIframes[r]=e;return t.GetPattern(r++,false,"iframe")}});return e},ReplaceStyleBySymCode:function(e){this.arStyles={};var t=this,r=0;e=e.replace(/<style[\s\S]*?\/style>/gi,function(e){t.arStyles[r]=e;return t.GetPattern(r++,false,"style")});return e},ReplaceObjectBySymCode:function(e){this.arObjects={};var t=this,r=0;e=e.replace(/<object[\s\S]*?\/object>/gi,function(e){t.arObjects[r]=e;return t.GetPattern(r++,false,"object")});e=e.replace(/<embed[\s\S]*?(?:\/embed)?>/gi,function(e){t.arObjects[r]=e;return t.GetPattern(r++,false,"object")});return e},CheckForVideo:function(e){var t=/(?:src)\s*=\s*("|')([\s\S]*?((?:youtube.com)|(?:youtu.be)|(?:rutube.ru)|(?:vimeo.com))[\s\S]*?)(\1)/gi;var r=t.exec(e);if(r){return{src:r[2],provider:this.GetVideoProviderName(r[3])}}else{return false}},GetVideoProviderName:function(e){var t="";switch(e){case"youtube.com":case"youtu.be":t="YouTube";break;case"rutube.ru":t="Rutube";break;case"vimeo.com":t="Vimeo";break}return t},SavePhpCode:function(e,t){this.arScripts[t]=e;return this.GetPhpPattern(t,false)},GetPhpPattern:function(e,t){if(t)return new RegExp("#BXPHP_"+e+"#","ig");else return"#BXPHP_"+e+"#"},GetPattern:function(e,t,r){var i;switch(r){case"php":i="#BXPHP_";break;case"javascript":i="#BXJAVASCRIPT_";break;case"html_comment":i="#BXHTMLCOMMENT_";break;case"iframe":i="#BXIFRAME_";break;case"style":i="#BXSTYLE_";break;case"video":i="#BXVIDEO_";break;case"object":i="#BXOBJECT_";break;default:return""}return t?new RegExp(i+e+"#","ig"):i+e+"#"},ParseSymCode:function(e){var t=this;e=e.replace(/#BX(PHP|JAVASCRIPT|HTMLCOMMENT|IFRAME|STYLE|VIDEO|OBJECT)_(\d+)#/g,function(e,r,i){var s="";if(t.IsAllowed(r.toLowerCase())){switch(r){case"PHP":s=t.GetPhpCodeHTML(t.arScripts[i]);break;case"JAVASCRIPT":s=t.GetJavascriptCodeHTML(t.arJavascripts[i]);break;case"HTMLCOMMENT":s=t.GetHtmlCommentHTML(t.arHtmlComments[i]);break;case"IFRAME":s=t.GetIframeHTML(t.arIframes[i]);break;case"STYLE":s=t.GetStyleHTML(t.arStyles[i]);break;case"VIDEO":s=t.GetVideoHTML(t.arVideos[i]);break;case"OBJECT":s=t.GetObjectHTML(t.arObjects[i]);break}}return s||e});return e},GetPhpCodeHTML:function(e){if(typeof e!=="string")return null;var t="",r=this.editor.components.IsComponent(e);if(r!==false){var i=this.editor.components.GetComponentData(r.name),s=i.title||r.name,a=i.params&&i.params.DESCRIPTION?i.params.DESCRIPTION:a;if(i.className){r.className=i.className||""}t=this.GetSurrogateHTML("component",s,a,r)}else{if(this.editor.allowPhp){t=this.GetSurrogateHTML("php",BX.message("BXEdPhpCode"),BX.message("BXEdPhpCode")+": "+this.GetShortTitle(e,200),{value:e})}else{t=""}}return t},GetJavascriptCodeHTML:function(e){if(typeof e!=="string")return null;return this.GetSurrogateHTML("javascript","Javascript","Javascript: "+this.GetShortTitle(e,200),{value:e})},GetHtmlCommentHTML:function(e){if(typeof e!=="string")return null;return this.GetSurrogateHTML("htmlcomment",BX.message("BXEdHtmlComment"),BX.message("BXEdHtmlComment")+": "+this.GetShortTitle(e),{value:e})},GetIframeHTML:function(e){if(typeof e!=="string")return null;return this.GetSurrogateHTML("iframe",BX.message("BXEdIframe"),BX.message("BXEdIframe")+": "+this.GetShortTitle(e),{value:e})},GetStyleHTML:function(e){if(typeof e!=="string")return null;return this.GetSurrogateHTML("style",BX.message("BXEdStyle"),BX.message("BXEdStyle")+": "+this.GetShortTitle(e),{value:e})},GetVideoHTML:function(e){var t="video",r=e.params||this.FetchVideoIframeParams(e.html,e.provider);r.value=e.html;var i=this.editor.SetBxTag(false,{tag:t,name:r.title,params:r}),s=this.editor.SetBxTag(false,{tag:"surrogate_dd",params:{origParams:r,origId:i}});this.editor.SetBxTag({id:i},{tag:t,name:r.title,params:r,title:r.title,surrogateId:s});var a='<span id="'+i+'" title="'+r.title+'"  class="'+this.surrClass+" bxhtmled-video-surrogate"+'" '+'style="min-width:'+r.width+"px; max-width:"+r.width+"px; min-height:"+r.height+"px; max-height:"+r.height+'px"'+">"+'<img title="'+r.title+'" id="'+s+'" class="bxhtmled-surrogate-dd" src="'+this.editor.util.GetEmptyImage()+'"/>'+'<span class="bxhtmled-surrogate-inner"><span class="bxhtmled-video-icon"></span><span class="bxhtmled-comp-lable" spellcheck=false>'+r.title+"</span></span>"+"</span>";return a},GetObjectHTML:function(e){return this.GetSurrogateHTML("object",BX.message("BXEdObjectEmbed"),BX.message("BXEdObjectEmbed")+": "+this.GetShortTitle(e),{value:e})},FetchVideoIframeParams:function(e,t){var r=/((?:src)|(?:title)|(?:width)|(?:height))\s*=\s*("|')([\s\S]*?)(\2)/gi,i={src:"",width:180,height:100,title:t?BX.message("BXEdVideoTitleProvider").replace("#PROVIDER_NAME#",t):BX.message("BXEdVideoTitle"),origTitle:""};e.replace(r,function(e,t,r,s){t=t.toLowerCase();if(t=="width"||t=="height"){s=parseInt(s,10);if(s&&!isNaN(s)){i[t]=s}}else if(t=="title"){i.origTitle=BX.util.htmlspecialcharsback(s);i.title+=": "+s}else{i[t]=s}return e});return i},GetSurrogateHTML:function(e,t,r,i){if(r){r=BX.util.htmlspecialchars(r);r=r.replace('"','"')}if(!i){i={}}var s=this.editor.SetBxTag(false,{tag:e,name:t,params:i}),a=this.editor.SetBxTag(false,{tag:"surrogate_dd",params:{origParams:i,origId:s}});this.editor.SetBxTag({id:s},{tag:e,name:t,params:i,title:r,surrogateId:a});if(!this.surrogateTags.tag){this.surrogateTags.tag=1}var n='<span id="'+s+'" title="'+(r||t)+'"  class="'+this.surrClass+(i.className?" "+i.className:"")+'">'+this.GetSurrogateInner(a,r,t)+"</span>";return n},GetSurrogateNode:function(e,t,r,i){var s=this.editor.GetIframeDoc(),a=this.editor.SetBxTag(false,{tag:e,name:t,params:i,title:r}),n=this.editor.SetBxTag(false,{tag:"surrogate_dd",params:{origParams:i,origId:a}});if(!i)i={};this.editor.SetBxTag({id:a},{tag:e,name:t,params:i,title:r,surrogateId:n});if(!this.surrogateTags.tag){this.surrogateTags.tag=1}return BX.create("SPAN",{props:{id:a,title:r||t,className:this.surrClass+(i.className?" "+i.className:"")},html:this.GetSurrogateInner(n,r,t)},s)},GetSurrogateInner:function(e,t,r){return'<img title="'+(t||r)+'" id="'+e+'" class="bxhtmled-surrogate-dd" src="'+this.editor.util.GetEmptyImage()+'"/>'+'<span class="bxhtmled-surrogate-inner"><span class="bxhtmled-right-side-item-icon"></span><span class="bxhtmled-comp-lable" unselectable="on" spellcheck=false>'+BX.util.htmlspecialchars(r)+"</span></span>"},GetShortTitle:function(e,t){if(e.length>100)e=e.substr(0,100)+"...";return e},_GetUnParsedContent:function(e){var t=this;e=e.replace(/#BX(PHP|JAVASCRIPT|HTMLCOMMENT|IFRAME|STYLE|VIDEO|OBJECT)_(\d+)#/g,function(e,r,i){var s;switch(r){case"PHP":s=t.arScripts[i];break;case"JAVASCRIPT":s=t.arJavascripts[i];break;case"HTMLCOMMENT":s=t.arHtmlComments[i];break;case"IFRAME":s=t.arIframes[i];break;case"STYLE":s=t.arStyles[i];break;case"VIDEO":s=t.arVideos[i].html;break;case"OBJECT":s=t.arObjects[i].html;break}return s});return e},IsSurrogate:function(e){return e&&BX.hasClass(e,this.surrClass)},TrimPhpBrackets:function(e){if(e.substr(0,2)!="<?")return e;if(e.substr(0,5).toLowerCase()=="<?php")e=e.substr(5);else e=e.substr(2);e=e.substr(0,e.length-2);return e},TrimQuotes:function(e,t){var r,i;e=e.trim();if(t==undefined){r=e.substr(0,1);i=e.substr(0,1);if(r=='"'&&i=='"'||r=="'"&&i=="'")e=e.substring(1,e.length-1)}else{if(!t.length)return e;r=e.substr(0,1);i=e.substr(0,1);t=t.substr(0,1);if(r==t&&i==t)e=e.substring(1,e.length-1)}return e},CleanCode:function(e){var t=false,r=false,i="",s=-1,a,n,o;while(s<e.length-1){s++;a=e.substr(s,1);if(!r){if(a=="/"&&s+1<e.length){n=0;if(e.substr(s+1,1)=="*"&&(n=e.indexOf("*/",s+2))>=0)n+=2;else if(e.substr(s+1,1)=="/"&&(n=e.indexOf("\n",s+2))>=0)n+=1;if(n>0){if(s>n)alert("iti="+s+"="+n);s=n}continue}if(a==" "||a=="\r"||a=="\n"||a=="	")continue}if(r&&a=="\\"){t=true;i+=a;continue}if(a=='"'||a=="'"){if(r){if(!t&&o==a)r=false}else{r=true;o=a}}t=false;i+=a}return i},ParseFunction:function(e){var t=e.indexOf("("),r=e.lastIndexOf(")");if(t>=0&&r>=0&&t<r)return{name:e.substr(0,t),params:e.substring(t+1,r)};return false},ParseParameters:function(e){e=this.CleanCode(e);var t=this.GetParams(e),r,i,s=t.length;for(i=0;i<s;i++){if(t[i].substr(0,6).toLowerCase()=="array("){t[i]=this.GetArray(t[i])}else{r=this.TrimQuotes(t[i]);if(this.IsNum(r)||t[i]!=r)t[i]=r;else t[i]=this.WrapPhpBrackets(t[i])}}return t},GetArray:function(e){var t={};if(e.substr(0,6).toLowerCase()!="array(")return e;e=e.substring(6,e.length-1);var r=this.GetParams(e),i,s,a,n;for(n=0;n<r.length;n++){if(r[n].substr(0,6).toLowerCase()=="array("){t[n]=this.GetArray(r[n]);continue}a=r[n].indexOf("=>");if(a==-1){if(r[n]==this.TrimQuotes(r[n]))t[n]=this.WrapPhpBrackets(r[n]);else t[n]=this.TrimQuotes(r[n])}else{i=this.TrimQuotes(r[n].substr(0,a));s=r[n].substr(a+2);if(s==this.TrimQuotes(s))s=this.WrapPhpBrackets(s);else s=this.TrimQuotes(s);if(s.substr(0,6).toLowerCase()=="array(")s=this.GetArray(s);t[i]=s}}return t},WrapPhpBrackets:function(e){e=e.trim();var t=e.substr(0,1),r=e.substr(0,1);if(t=='"'&&r=='"'||t=="'"&&r=="'")return e;return"={"+e+"}"},GetParams:function(e){var t=[],r=0,i,s,a=1,n=1,o,l="";for(o=0;o<e.length;o++){i=e.substr(o,1);if(i=='"'&&n==1&&!s){a*=-1}else if(i=="'"&&a==1&&!s){n*=-1}else if(i=="\\"&&!s){s=true;l+=i;continue}if(s)s=false;if(n==-1||a==-1){l+=i;continue}if(i=="("){r++}else if(i==")"){r--}else if(i==","&&r==0){t.push(l);l="";continue}if(r<0)break;l+=i}if(l!="")t.push(l);return t},IsNum:function(e){var t=e;e=parseFloat(t);if(isNaN(e))e=parseInt(t);if(!isNaN(e))return t==e;return false},ParseBxNodes:function(e){var t,r=this.editor.parser.convertedBxNodes,i=r.length;for(t=0;t<i;t++){if(r[t].tag=="surrogate_dd"){e=e.replace("~"+r[t].params.origId+"~","")}}this._skipNodeIndex={};this._skipNodeList=[];var s=this;e=e.replace(/~(bxid\d{1,9})~/gi,function(e,t){if(!s._skipNodeIndex[t]){var r=s.editor.GetBxTag(t);if(r&&r.tag){var i=s.GetBxNode(r.tag);if(i){return i.Parse(r.params)}}}return""});return e},GetBxNodeList:function(){var e=this;this.arBxNodes={component:{Parse:function(t){return e.editor.components.GetSource(t)}},component_icon:{Parse:function(t){return e.editor.components.GetOnDropHtml(t)}},surrogate_dd:{Parse:function(t){if(BX.browser.IsFirefox()||!t||!t.origId){return""}var r=e.editor.GetBxTag(t.origId);if(r){e._skipNodeIndex[t.origId]=true;e._skipNodeList.push(t.origId);var i=e.GetBxNode(r.tag);if(i){return i.Parse(r.params)}}return"#parse surrogate_dd#"}},php:{Parse:function(t){return e._GetUnParsedContent(t.value)}},php_protected:{Parse:function(e){return e.value}},javascript:{Parse:function(t){return e._GetUnParsedContent(t.value)}},htmlcomment:{Parse:function(t){return e._GetUnParsedContent(t.value)}},iframe:{Parse:function(t){return e._GetUnParsedContent(t.value)}},style:{Parse:function(t){return e._GetUnParsedContent(t.value)}},video:{Parse:function(t){return e._GetUnParsedContent(t.value)}},object:{Parse:function(t){return e._GetUnParsedContent(t.value)}},anchor:{Parse:function(e){var t="";if(e.attributes){for(var r in e.attributes){if(e.attributes.hasOwnProperty(r)){t+=r+'="'+e.attributes[r]+'" '}}}return"<a "+t+(e.name?'name="'+e.name+'"':"")+">"+e.html+"</a>"}},pagebreak:{Parse:function(e){return"<BREAK />"}},printbreak:{Parse:function(e){return'<div style="page-break-after: always">'+e.innerHTML+"</div>"}}};this.editor.On("OnGetBxNodeList");return this.arBxNodes},AddBxNode:function(e,t){if(this.arBxNodes==undefined){var r=this;BX.addCustomEvent(this.editor,"OnGetBxNodeList",function(){r.arBxNodes[e]=t})}else{this.arBxNodes[e]=t}},GetBxNode:function(e){if(!this.arBxNodes){this.arBxNodes=this.GetBxNodeList()}return this.arBxNodes[e]||null},OnSurrogateMousedown:function(e,t,r){var i=this;if(r.tag=="surrogate_dd"){BX.bind(t,"dragstart",function(e){i.OnSurrogateDragStart(e,this)});BX.bind(t,"dragend",function(e){i.OnSurrogateDragEnd(e,this,r)})}else{setTimeout(function(){var e=i.CheckParentSurrogate(i.editor.selection.GetSelectedNode());if(e){i.editor.selection.SetAfter(e);if(!e.nextSibling||e.nextSibling.nodeType!=3){var t=i.editor.util.GetInvisibleTextNode();i.editor.selection.InsertNode(t);i.editor.selection.SetAfter(t)}}},0)}},OnSurrogateDragEnd:function(e,t,r){if(!document.querySelectorAll)return;var i=this.editor.GetIframeDoc(),s,a,n,o={},l=i.querySelectorAll(".bxhtmled-surrogate"),d=i.querySelectorAll(".bxhtmled-surrogate-dd"),u=l.length;for(s=0;s<d.length;s++){if(d[s]&&d[s].id==r.id){BX.remove(d[s])}}for(s=0;s<u;s++){a=l[s];if(o[a.id]){if(a.getAttribute("data-bx-paste-flag")=="Y"||o[a.id].getAttribute("data-bx-paste-flag")!="Y")BX.remove(a);else if(o[a.id].getAttribute("data-bx-paste-flag")=="Y")BX.remove(o[a.id])}else{o[a.id]=a;n=this.editor.GetBxTag(a.id);a.innerHTML=this.GetSurrogateInner(n.surrogateId,n.title,n.name)}}},OnSurrogateDragStart:function(e,t){if(BX.browser.IsFirefox()){this.editor.GetIframeDoc().body.appendChild(t)}},CheckParentSurrogate:function(e){if(!e){return false}if(this.IsSurrogate(e)){return e}var t=this,r=0,i=BX.findParent(e,function(e){return r++>4||t.IsSurrogate(e)},this.editor.GetIframeDoc().body);return this.IsSurrogate(i)?i:false},CheckSurrogateDd:function(e){return e&&e.nodeType==1&&this.editor.GetBxTag(e).tag=="surrogate_dd"},OnSurrogateClick:function(e,t){var r=this.editor.GetBxTag(t);if(r&&r.tag=="surrogate_dd"){var i=this.editor.GetBxTag(r.params.origId);this.editor.On("OnSurrogateClick",[r,i,t,e])}},OnSurrogateDblClick:function(e,t){var r=this.editor.GetBxTag(t);if(r&&r.tag=="surrogate_dd"){var i=this.editor.GetBxTag(r.params.origId);this.editor.On("OnSurrogateDblClick",[r,i,t,e])}},OnSurrogateKeyup:function(e,t,r,i){var s,a,n=this.editor.selection.GetRange();if(n){if(n.collapsed){}else{}}},OnSurrogateKeydown:function(e,t,r,i){var s,a=this.editor.KEY_CODES,n=this.editor.selection.GetRange(),o,l,d,u=i;if(!n.collapsed){if(t===a["backspace"]||t===a["delete"]){var f,h=n.getNodes([3]);for(f=0;f<h.length;f++){s=this.editor.util.CheckSurrogateNode(h[f]);if(s){l=this.editor.GetBxTag(s);if(this.surrogateTags[l.tag]){this.RemoveSurrogate(s,l)}}}}}if(t===a["delete"]&&n.collapsed){o=this.editor.util.GetInvisibleTextNode();this.editor.selection.InsertNode(o);this.editor.selection.SetAfter(o);var c=o.nextSibling;if(c){if(c&&c.nodeName=="BR"){c=c.nextSibling}if(c&&c.nodeType==3&&(c.nodeValue=="\n"||this.editor.util.IsEmptyNode(c))){c=c.nextSibling}if(c){BX.remove(o);l=this.editor.GetBxTag(c);if(this.surrogateTags[l.tag]){this.RemoveSurrogate(c,l);return BX.PreventDefault(e)}}}}else if(t===a["backspace"]&&n.collapsed){o=this.editor.util.GetInvisibleTextNode();

this.editor.selection.InsertNode(o);this.editor.selection.SetAfter(o);var g=this.editor.util.GetPreviousNotEmptySibling(o);if(g&&this.editor.phpParser.IsSurrogate(g)){BX.remove(g);if(o)BX.remove(o);return BX.PreventDefault(e)}else{if(o)BX.remove(o)}}if(n.startContainer==n.endContainer&&n.startContainer.nodeName!=="BODY"){u=n.startContainer;d=this.editor.util.CheckSurrogateNode(u);if(d){l=this.editor.GetBxTag(d.id);if(t===a["backspace"]||t===a["delete"]){this.RemoveSurrogate(d,l);BX.PreventDefault(e)}else if(t===a["left"]||t===a["up"]){var p=d.previousSibling;if(p&&p.nodeType==3&&this.editor.util.IsEmptyNode(p))this.editor.selection._MoveCursorBeforeNode(p);else this.editor.selection._MoveCursorBeforeNode(d);return BX.PreventDefault(e)}else if(t===a["right"]||t===a["down"]){var m=d.nextSibling;if(m&&m.nodeType==3&&this.editor.util.IsEmptyNode(m))this.editor.selection._MoveCursorAfterNode(m);else this.editor.selection._MoveCursorAfterNode(d);return BX.PreventDefault(e)}else if(t===a.shift||t===a.ctrl||t===a.alt||t===a.cmd||t===a.cmdRight){return BX.PreventDefault(e)}else{this.editor.selection._MoveCursorAfterNode(d)}}}},RemoveSurrogate:function(e,t){this.editor.undoManager.Transact();BX.remove(e);this.editor.On("OnSurrogateRemove",[e,t])},CheckHiddenSurrogateDrag:function(){var e,t;for(t=0;t<this.hiddenDd.length;t++){e=this.editor.GetIframeElement(this.hiddenDd[t]);if(e){e.style.visibility=""}}this.hiddenDd=[]},GetAllSurrogates:function(e){if(!document.querySelectorAll)return[];e=e===true;var t=this.editor.GetIframeDoc(),r=[],i,s,a,n=t.querySelectorAll(".bxhtmled-surrogate");for(i=0;i<n.length;i++){s=n[i];a=this.editor.GetBxTag(s.id);if(a.tag||e){r.push({node:s,bxTag:a})}}return r},RenewSurrogates:function(){var e=true,t,r={},i,s=this.GetAllSurrogates(true);for(t=0;t<s.length;t++){if(!s[t].bxTag.tag){BX.remove(s[t].node);continue}i=s[t].bxTag.surrogateId;if(!r[i]||!e){r[i]=i;s[t].node.innerHTML=this.GetSurrogateInner(s[t].bxTag.surrogateId,s[t].bxTag.title,s[t].bxTag.name)}else{BX.remove(s[t].node)}}},RedrawSurrogates:function(){var e,t=this.GetAllSurrogates();for(e=0;e<t.length;e++){if(t[e].node){BX.addClass(t[e].node,"bxhtmled-surrogate-tmp")}}setTimeout(function(){for(e=0;e<t.length;e++){if(t[e].node){BX.removeClass(t[e].node,"bxhtmled-surrogate-tmp")}}},0)},IsAllowed:function(e){return this.allowed[e]},AdvancedPhpParse:function(e){if(this.bUseAPP){this.arAPPFragments=[];e=this.AdvancedPhpParseInAttributes(e)}return e},AdvancedPhpParseBetweenTableTags:function(e){var t=this;function r(e,r,i,s,a){t.arAPPFragments.push(JS_addslashes(r));return i+s+' data-bx-php-before="#BXAPP'+(t.arAPPFragments.length-1)+'#" '+a}function i(e,r,i,s,a){t.arAPPFragments.push(JS_addslashes(a));return r+">"+s+"<"+i+' style="display:none;" data-bx-php-after="#BXAPP'+(t.arAPPFragments.length-1)+'#"></'+i+">"}var s=t.APPConfig.arTags_before,a=t.APPConfig.arTags_after,n,o,d;for(o=0;o<s.length;o++){n=s[o];if(t.limit_php_access)d=new RegExp("#(PHP(?:\\d{4}))#(\\s*)(<"+n+"[^>]*?)(>)","ig");else d=new RegExp("<\\?(.*?)\\?>(\\s*)(<"+n+"[^>]*?)(>)","ig");e=e.replace(d,r)}for(o=0,l=a.length;o<l;o++){n=a[o];if(t.limit_php_access)d=new RegExp("(</("+n+")[^>]*?)>(\\s*)#(PHP(?:\\d{4}))#","ig");else d=new RegExp("(</("+n+")[^>]*?)>(\\s*)<\\?(.*?)\\?>","ig");e=e.replace(d,i)}return e},AdvancedPhpParseInAttributes:function(e){var t=this,r=this.APPConfig.arTags,i,s,a,n;function o(e,r,i,s,a,n,o){if(a.indexOf("#BXPHP_")===-1){return e}t.arAPPFragments.push(a);var l=t.arAPPFragments.length-1;var d=t.AdvancedPhpGetFragmentByIndex(l,true);return r+i+'="'+d+'"'+" data-bx-app-ex-"+i+'="#BXAPP'+l+'#"'+n}for(i in r){if(r.hasOwnProperty(i)){for(a=0;a<r[i].length;a++){s=r[i][a];n=new RegExp("(<"+i+"(?:[^>](?:\\?>)*?)*?)("+s+")\\s*=\\s*((?:\"|')?)([\\s\\S]*?)\\3((?:[^>](?:\\?>)*?)*?>)","ig");e=e.replace(n,o)}}}return e},AdvancedPhpUnParse:function(e){return e},AdvancedPhpGetFragmentByCode:function(e,t){var r=e.substr(6);r=parseInt(r.substr(0,r.length-1),10);return this.AdvancedPhpGetFragmentByIndex(r,t)},AdvancedPhpGetFragmentByIndex:function(e,t){var r=this,i=this.arAPPFragments[e];i=i.replace(/#BXPHP_(\d+)#/g,function(e,i){var s=r.arScripts[parseInt(i,10)];if(t){var a=r.GetSiteTemplatePath();if(a){s=s.replace(/<\?=\s*SITE_TEMPLATE_PATH;?\s*\?>/i,a);s=s.replace(/<\?\s*echo\s*SITE_TEMPLATE_PATH;?\s*\?>/i,a)}}return s});return i},ParseBreak:function(e){var t=this;e=e.replace(/<break\s*\/*>/gi,function(e){return t.GetSurrogateHTML("pagebreak",BX.message("BXEdPageBreakSur"),BX.message("BXEdPageBreakSurTitle"))});return e},GetSiteTemplatePath:function(){return this.editor.GetTemplateParams().SITE_TEMPLATE_PATH},CustomContentParse:function(e){for(var t=0;t<this.customParsers.length;t++){if(typeof this.customParsers[t]=="function"){e=this.customParsers[t](e)}}return e},AddCustomParser:function(e){if(typeof e=="function")this.customParsers.push(e)}};function r(e){this.editor=e;this.parseAlign=true}r.prototype={Unparse:function(e){var t=this.editor.parser.GetAsDomElement(e,this.editor.GetIframeDoc());t.setAttribute("data-bx-parent-node","Y");e=this.GetNodeHtml(t,true);e=e.replace(/#BR#/gi,"\n");e=e.replace(/&nbsp;/gi," ");e=e.replace(/\uFEFF/gi,"");return e},Parse:function(e){var t=this,r,i;e=e.replace(/</gi,"&lt;");e=e.replace(/>/gi,"&gt;");function s(e){if(!e.replace)return e;return e.replace(/("|<|>)/g,"")}var a=[];e=e.replace(/\[code\]((?:\s|\S)*?)\[\/code\]/gi,function(e,t){a.push('<pre class="bxhtmled-code">'+t+"</pre>");return"#BX_CODE"+(a.length-1)+"#"});var n,o;for(n in this.editor.parser.specialParsers){if(this.editor.parser.specialParsers.hasOwnProperty(n)){o=this.editor.parser.specialParsers[n];if(o&&o.Parse){e=o.Parse(n,e,this.editor)}}}if(this.editor.sortedSmiles){var l=[],d=[],u;e=e.replace(/\[(?:\s|\S)*?\]/gi,function(e){d.push(e);return"#BX_TMP_TAG"+(d.length-1)+"#"});e=e.replace(/(?:https?|ftp):\/\//gi,function(e){l.push(e);return"#BX_TMP_URL"+(l.length-1)+"#"});i=this.editor.sortedSmiles.length;var f,h="\\s.,;:!?\\#\\-\\*\\|\\[\\]\\(\\)\\{\\}<>&\\n\\t\\r";for(r=0;r<i;r++){u=this.editor.sortedSmiles[r];if(u.path&&u.code){f='<img id="'+t.editor.SetBxTag(false,{tag:"smile",params:u})+'" src="'+u.path+'" title="'+(u.name||u.code)+'"/>';e=e.replace(new RegExp("(["+h+"])"+BX.util.preg_quote(u.code)+"(["+h+"])","ig"),"$1"+f+"$2");e=e.replace(new RegExp("(["+h+"])"+BX.util.preg_quote(u.code)+"$","ig"),"$1"+f);e=e.replace(new RegExp("^"+BX.util.preg_quote(u.code)+"(["+h+"])","ig"),f+"$1");e=e.replace(new RegExp("^"+BX.util.preg_quote(u.code)+"$","ig"),f)}}if(l.length>0){e=e.replace(/#BX_TMP_URL(\d+)#/gi,function(e,t){return l[t]||e})}if(d.length>0){e=e.replace(/#BX_TMP_TAG(\d+)#/gi,function(e,t){return d[t]||e})}}e=e.replace(/\[quote\]/gi,'<blockquote class="bxhtmled-quote">');e=e.replace(/\[\/quote\]\n?/gi,"</blockquote>");e=e.replace(/[\r\n\s\t]?\[table\][\r\n\s\t]*?\[tr\]/gi,'<table border="1">[TR]');e=e.replace(/\[tr\][\r\n\s\t]*?\[td\]/gi,"[TR][TD]");e=e.replace(/\[tr\][\r\n\s\t]*?\[th\]/gi,"[TR][TH]");e=e.replace(/\[\/td\][\r\n\s\t]*?\[td\]/gi,"[/TD][TD]");e=e.replace(/\[\/tr\][\r\n\s\t]*?\[tr\]/gi,"[/TR][TR]");e=e.replace(/\[\/td\][\r\n\s\t]*?\[\/tr\]/gi,"[/TD][/TR]");e=e.replace(/\[\/th\][\r\n\s\t]*?\[\/tr\]/gi,"[/TH][/TR]");e=e.replace(/\[\/tr\][\r\n\s\t]*?\[\/table\][\r\n\s\t]?/gi,"[/TR][/TABLE]");e=e.replace(/[\r\n\s\t]*?\[\/list\]/gi,"[/LIST]");e=e.replace(/[\r\n\s\t]*?\[\*\]?/gi,"[*]");var c=["b","u","i",["s","del"],"table","tr","td","th"],g,p;i=c.length;for(r=0;r<i;r++){if(typeof c[r]=="object"){g=c[r][0];p=c[r][1]}else{g=p=c[r]}e=e.replace(new RegExp("\\[(\\/?)"+g+"\\]","ig"),"<$1"+p+">")}e=e.replace(/\[url\]((?:\s|\S)*?)\[\/url\]/gi,function(e,t){return'<a href="'+s(t)+'">'+t+"</a>"});e=e.replace(/\[url\s*=\s*((?:\s|\S)*?)\]((?:\s|\S)*?)\[\/url\]/gi,function(e,t,r){return'<a href="'+s(t)+'">'+r+"</a>"});e=e.replace(/\[img((?:\s|\S)*?)\]((?:\s|\S)*?)\[\/img\]/gi,function(e,r,i){r=t.FetchImageParams(r);i=s(i);var a="";if(r.width)a+="width:"+parseInt(r.width)+"px;";if(r.height)a+="height:"+parseInt(r.height)+"px;";if(a!=="")a='style="'+a+'"';return'<img  src="'+i+'"'+a+"/>"});r=0;while(e.toLowerCase().indexOf("[color=")!=-1&&e.toLowerCase().indexOf("[/color]")!=-1&&r++<20){e=e.replace(/\[color=((?:\s|\S)*?)\]((?:\s|\S)*?)\[\/color\]/gi,function(e,t,r){return'<span style="color:'+s(t)+'">'+r+"</span>"})}r=0;while(e.toLowerCase().indexOf("[list=")!=-1&&e.toLowerCase().indexOf("[/list]")!=-1&&r++<20){e=e.replace(/\[list=1\]((?:\s|\S)*?)\[\/list\]/gi,"<ol>$1</ol>")}r=0;while(e.toLowerCase().indexOf("[list")!=-1&&e.toLowerCase().indexOf("[/list]")!=-1&&r++<20){e=e.replace(/\[list\]((?:\s|\S)*?)\[\/list\]/gi,"<ul>$1</ul>")}e=e.replace(/\[\*\]/gi,"<li>");r=0;while(e.toLowerCase().indexOf("[font=")!=-1&&e.toLowerCase().indexOf("[/font]")!=-1&&r++<20){e=e.replace(/\[font=((?:\s|\S)*?)\]((?:\s|\S)*?)\[\/font\]/gi,function(e,t,r){return'<span style="font-family:'+s(t)+'">'+r+"</span>"})}r=0;while(e.toLowerCase().indexOf("[size=")!=-1&&e.toLowerCase().indexOf("[/size]")!=-1&&r++<20){e=e.replace(/\[size=((?:\s|\S)*?)\]((?:\s|\S)*?)\[\/size\]/gi,function(e,t,r){return'<span style="font-size:'+s(t)+'">'+r+"</span>"})}if(this.parseAlign){e=e.replace(/\[(center|left|right|justify)\]/gi,function(e,t){return'<div style="text-align:'+s(t)+'">'});e=e.replace(/\[\/(center|left|right|justify)\]/gi,"</div>")}if(e.toLowerCase().indexOf("[/video]")!=-1){e=e.replace(/\[video((?:\s|\S)*?)\]((?:\s|\S)*?)\[\/video\]/gi,function(e,r,i){return t.GetVideoSourse(i,t.FetchVideoParams(r.trim(r)),e)})}e=e.replace(/\n/gi,"<br />");e=e.replace(/&#91;/gi,"[");e=e.replace(/&#93;/gi,"]");if(a.length>0){e=e.replace(/#BX_CODE(\d+)#/gi,function(e,t){return a[t]||e})}return e},GetNodeHtml:function(e,t){var r={node:e},i="";if(!t){if(e.nodeType==3){var s=BX.util.htmlspecialchars(e.nodeValue);if(!s.match(/[^\n]+/gi)&&e.previousSibling&&e.nextSibling&&this.editor.util.IsBlockNode(e.previousSibling)&&this.editor.util.IsBlockNode(e.nextSibling)){return"\n"}if(BX.browser.IsChrome()&&this.editor.pasteHandleMode&&e.nextSibling&&e.nextSibling.nodeName=="P"){s=s.replace(/\n+/gi,"\n")}if(e.parentNode&&!e.parentNode.getAttribute("data-bx-parent-node")&&BX.util.in_array(e.parentNode.nodeName,["P","DIV","SPAN","TD","TH","B","STRONG","I","EM","U","DEL","S","STRIKE"])){s=s.replace(/\n/gi," ")}if(BX.browser.IsMac()){s=s.replace(/\n/gi," ")}s=s.replace(/\[/gi,"&#91;");s=s.replace(/\]/gi,"&#93;");return s}if(e.nodeType==1&&e.nodeName=="P"){var a=BX.util.trim(e.innerHTML);a=a.replace(/[\n\r\s]/gi,"").toLowerCase();if(a=="<br>"){e.innerHTML=""}}var n=this.UnParseNodeBB(r);if(n!==false){return n}if(r.bbOnlyChild)t=true;if(!t){if(r.breakLineBefore){i+="\n"}if(e.nodeType==1&&!r.hide){i+="["+r.bbTag;if(r.bbValue){i+="="+r.bbValue}i+="]"}}}if(r.checkNodeAgain){i+=this.GetNodeHtml(e)}else{var o,l,d="";for(o=0;o<e.childNodes.length;o++){l=e.childNodes[o];d+=this.GetNodeHtml(l)}i+=d}if(!t){if(r.breakLineAfter)i+="\n";if(d==""&&this.IsPairNode(r.bbTag)&&e.nodeName!=="P"&&e.nodeName!=="TD"&&e.nodeName!=="TR"&&e.nodeName!=="TH"){return""}if(e.nodeType==1&&(e.childNodes.length>0||this.IsPairNode(r.bbTag))&&!r.hide&&!r.hideRight){i+="[/"+r.bbTag+"]"}if(r.breakLineAfterEnd||e.nodeType==1&&this.editor.util.IsBlockNode(e)&&this.editor.util.IsBlockNode(e.nextSibling)){i+="\n"}}return i},UnParseNodeBB:function(e){var t,r=["TABLE","TD","TR","TH","TBODY","TFOOT","THEAD","CAPTION","COL","COLGROUP"],i=false,s=false,a=e.node.nodeName.toUpperCase();e.checkNodeAgain=false;if(a=="BR"){return"#BR#"}if(e.node&&e.node.id){t=this.editor.GetBxTag(e.node.id);if(t.tag){var n=this.editor.parser.specialParsers[t.tag];if(n&&n.UnParse){return n.UnParse(t,e,this.editor)}else if(t.tag=="video"){return t.params.value}else if(t.tag=="smile"){return t.params.code}else{return""}}}if(a=="IFRAME"&&e.node.src){var o=e.node.src.replace(/https?:\/\//gi,"//"),l=this.editor.phpParser.CheckForVideo('src="'+o+'"');if(l){var d=parseInt(e.node.width),u=parseInt(e.node.height);return"[VIDEO TYPE="+l.provider.toUpperCase()+" WIDTH="+d+" HEIGHT="+u+"]"+o+"[/VIDEO]"}}if(a=="PRE"&&BX.hasClass(e.node,"bxhtmled-code")){return"[CODE]"+this.GetCodeContent(e.node)+"[/CODE]"}if(a=="IMG"){var f="";if(e.node.style.width)f+=" WIDTH="+parseInt(e.node.style.width);else if(e.node.width)f+=" WIDTH="+parseInt(e.node.width);if(e.node.style.height)f+=" HEIGHT="+parseInt(e.node.style.height);else if(e.node.height)f+=" HEIGHT="+parseInt(e.node.height);return"[IMG"+f+"]"+e.node.src+"[/IMG]"}e.hide=false;e.bbTag=a;i=BX.util.in_array(a,r);s=this.parseAlign&&(e.node.style.textAlign||e.node.align)&&!i;if(a=="STRONG"||a=="B"){e.bbTag="B"}else if(a=="EM"||a=="I"){e.bbTag="I"}else if(a=="DEL"||a=="S"){e.bbTag="S"}else if(a=="OL"||a=="UL"){e.bbTag="LIST";e.breakLineAfter=true;e.bbValue=a=="OL"?"1":""}else if(a=="LI"){e.bbTag="*";e.breakLineBefore=true;e.hideRight=true}else if(a=="A"){e.bbTag="URL";e.bbValue=this.editor.parser.GetAttributeEx(e.node,"href");e.bbValue=e.bbValue.replace(/\[/gi,"&#91;").replace(/\]/gi,"&#93;");if(e.bbValue===""){e.bbOnlyChild=true}}else if(e.node.style.color&&!i){e.bbTag="COLOR";e.bbValue=this.editor.util.RgbToHex(e.node.style.color);e.node.style.color="";if(e.node.style.cssText!=""){e.checkNodeAgain=true}}else if(e.node.style.fontFamily&&!i){e.bbTag="FONT";e.bbValue=e.node.style.fontFamily;e.node.style.fontFamily="";if(e.node.style.cssText!=""){e.checkNodeAgain=true}}else if(e.node.style.fontSize&&!i){e.bbTag="SIZE";e.bbValue=e.node.style.fontSize;e.node.style.fontSize="";if(e.node.style.cssText!=""){e.checkNodeAgain=true}}else if(a=="BLOCKQUOTE"&&e.node.className=="bxhtmled-quote"&&!e.node.getAttribute("data-bx-skip-check")){e.bbTag="QUOTE";e.breakLineAfterEnd=true;if(s){e.checkNodeAgain=true;e.node.setAttribute("data-bx-skip-check","Y")}}else if(s){var h=e.node.style.textAlign||e.node.align;if(BX.util.in_array(h,["left","right","center","justify"])){e.hide=false;e.bbTag=h.toUpperCase()}else{e.hide=!BX.util.in_array(a,this.editor.BBCODE_TAGS)}}else if(!BX.util.in_array(a,this.editor.BBCODE_TAGS)){e.hide=true}return false},IsPairNode:function(e){e=e.toUpperCase();return!(e.substr(0,1)=="H"||e=="BR"||e=="IMG"||e=="INPUT")},GetCodeContent:function(e){if(!e||this.editor.util.IsEmptyNode(e))return"";var t,r="";for(t=0;t<e.childNodes.length;t++){if(e.childNodes[t].nodeType==3)r+=e.childNodes[t].data;else if(e.childNodes[t].nodeType==1&&e.childNodes[t].nodeName=="BR")r+="#BR#";else r+=this.GetCodeContent(e.childNodes[t])}if(BX.browser.IsIE())r=r.replace(/\r/gi,"#BR#");else r=r.replace(/\n/gi,"#BR#");r=r.replace(/\[/gi,"&#91;");r=r.replace(/\]/gi,"&#93;");return r},GetVideoSourse:function(e,t,r){return this.editor.phpParser.GetVideoHTML({params:{width:t.width,height:t.height,title:BX.message.BXEdVideoTitle,origTitle:"",provider:t.type},html:r})},FetchVideoParams:function(e){e=BX.util.trim(e);var t=e.split(" "),r,i,s,a,n={width:180,height:100,type:false};for(r=0;r<t.length;r++){a=t[r].split("=");i=a[0].toLowerCase();s=a[1];if(i=="width"||i=="height"){s=parseInt(s,10);if(s&&!isNaN(s)){n[i]=Math.max(s,100)}}else if(i=="type"){s=s.toUpperCase();if(s=="YOUTUBE"||s=="RUTUBE"||s=="VIMEO"){n[i]=s}}}return n},FetchImageParams:function(e){e=BX.util.trim(e);var t=e.split(" "),r,i,s,a,n={};for(r=0;r<t.length;r++){a=t[r].split("=");i=a[0].toLowerCase();s=a[1];if(i=="width"||i=="height"){s=parseInt(s,10);if(s&&!isNaN(s)){n[i]=s}}}return n}};function i(e){this.editor=e;var t=["area","hr","i?frame","link","meta","noscript","style","table","tbody","thead","tfoot"],r=["li","dt","dd","h[1-6]","option","script"];this.reBefore=new RegExp("^<(/?"+t.join("|/?")+"|"+r.join("|")+")[ >]","i");this.reAfter=new RegExp("^<(br|/?"+t.join("|/?")+"|/"+r.join("|/")+")[ >]");var i=["blockquote","div","dl","fieldset","form","frameset","map","ol","p","pre","select","td","th","tr","ul"];this.reLevel=new RegExp("^</?("+i.join("|")+")[ >]");this.lastCode=null;this.lastResult=null}i.prototype={Format:function(e){if(e!=this.lastCode){this.lastCode=e;this.lastResult=this.DoFormat(e)}return this.lastResult},DoFormat:function(e){e+=" ";this.level=0;var t,r,i=0,s=null,a=null,n="",o="",l="";for(t=0;t<e.length;t++){i=t;if(e.substr(t).indexOf("<")==-1){o+=e.substr(t);o=o.replace(/\n\s*\n/g,"\n");o=o.replace(/^[\s\n]*/,"");o=o.replace(/[\s\n]*$/,"");if(o.indexOf("<!--noindex-->")!==-1){o=o.replace(/(<!--noindex-->)(?:[\s|\n|\r|\t]*?)(<a[\s\S]*?\/a>)(?:[\s|\n|\r|\t]*?)(<!--\/noindex-->)(?:[\n|\r|\t]*)/gi,"$1$2$3")}return o}while(i<e.length&&e.charAt(i)!=="<"){i++}if(t!=i){l=e.substr(t,i-t);if(l.match(/^\s+$/)){l=l.replace(/\s+/g," ");o+=l}else{if(o.charAt(o.length-1)=="\n"){o+=this.GetTabs()}else if(l.charAt(0)=="\n"){o+="\n"+this.GetTabs();l=l.replace(/^\s+/,"")}l=l.replace(/\n/g," ");l=l.replace(/\n+/g,"");l=l.replace(/\s+/g," ");o+=l}if(l.match(/\n/)){o+="\n"+this.GetTabs()}}s=i;while(i<e.length&&e.charAt(i)!=">"){i++}n=e.substr(s,i-s);t=i;if(n.substr(1,3)==="!--"){if(!n.match(/--$/)){while(e.substr(i,3)!=="-->"){i++}i+=2;n=e.substr(s,i-s);t=i}if(o.charAt(o.length-1)!=="\n"){o+="\n"}o+=this.GetTabs();o+=n+">\n"}else if(n[1]==="!"){o=this.PutTag(n+">",o)}else if(n[1]=="?"){o+=n+">\n"}else if(r=n.match(/^<(script|style)/i)){r[1]=r[1].toLowerCase();o=this.PutTag(this.CleanTag(n),o);a=String(e.substr(t+1)).toLowerCase().indexOf("</"+r[1]);if(a){l=e.substr(t+1,a);t+=a;o+=l}}else{o=this.PutTag(this.CleanTag(n),o)}}return e},GetTabs:function(){var e="",t;for(t=0;t<this.level;t++){e+="	"}return e},CleanTag:function(e){var t,r=/\s*([^= ]+)(?:=((['"']).*?\3|[^ ]+))?/,i="",s="";e=e.replace(/\n/g," ");e=e.replace(/[\s]{2,}/g," ");e=e.replace(/^\s+|\s+$/g," ");if(e.match(/\/$/)){s="/";e=e.replace(/\/+$/,"")}while(t=r.exec(e)){if(t[2])i+=t[1]+"="+t[2];else if(t[1])i+=t[1];i+=" ";e=e.substr(t[0].length)}return i.replace(/\s*$/,"")+s+">"},PutTag:function(e,t){var r=e.match(this.reLevel);if(e.match(this.reBefore)||r){t=t.replace(/\s*$/,"");t+="\n"}if(r&&e.charAt(1)=="/"){this.level--}if(t.charAt(t.length-1)=="\n"){t+=this.GetTabs()}if(r&&"/"!=e.charAt(1)){this.level++}t+=e;if(e.match(this.reAfter)||e.match(this.reLevel)){t=t.replace(/ *$/,"");t+="\n"}return t}};function s(){window.BXHtmlEditor.BXCodeFormatter=i;window.BXHtmlEditor.BXEditorParser=e;window.BXHtmlEditor.BXEditorPhpParser=t;window.BXHtmlEditor.BXEditorBbCodeParser=r}if(window.BXHtmlEditor){s()}else{BX.addCustomEvent(window,"OnBXHtmlEditorInit",s)}})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:72:"/bitrix/js/fileman/html_editor/html-base-controls.min.js?145227744858155";s:6:"source";s:52:"/bitrix/js/fileman/html_editor/html-base-controls.js";s:3:"min";s:56:"/bitrix/js/fileman/html_editor/html-base-controls.min.js";s:3:"map";s:56:"/bitrix/js/fileman/html_editor/html-base-controls.map.js";}"*/
(function(){function t(t,e){this.editor=t;this.bShown=false;this.closedWidth=20;this.MIN_CLOSED_WIDTH=120;this.width=this.editor.config.taskbarWidth||250;this.taskbars={};this.freezeOnclickHandler=false;if(e){this.Init()}}t.prototype={Init:function(){this.pCont=this.editor.dom.taskbarCont;this.pCont.setAttribute("data-bx-type","taskbarmanager");this.pResizer=BX("bx-html-editor-tskbr-res-"+this.editor.id);this.pResizer.setAttribute("data-bx-type","taskbarflip");this.pTopCont=BX("bx-html-editor-tskbr-top-"+this.editor.id);BX.bind(this.pResizer,"mousedown",BX.proxy(this.StartResize,this));BX.bind(this.pCont,"click",BX.proxy(this.OnClick,this));this.pSearchCont=BX("bxhed-tskbr-search-cnt-"+this.editor.id);this.pSearchAli=BX("bxhed-tskbr-search-ali-"+this.editor.id);this.pSearchInput=BX("bxhed-tskbr-search-inp-"+this.editor.id);this.pSearchNothingNotice=BX("bxhed-tskbr-search-nothing-"+this.editor.id);BX.bind(this.pSearchInput,"keyup",BX.proxy(this.TaskbarSearch,this))},OnClick:function(t){if(!t)t=window.event;if(this.freezeOnclickHandler)return;var e=this,i=t.target||t.srcElement,s=i&&i.getAttribute?i.getAttribute("data-bx-type"):null;if(!s){i=BX.findParent(i,function(t){return t==e.pCont||t.getAttribute&&t.getAttribute("data-bx-type")},this.pCont);s=i&&i.getAttribute?i.getAttribute("data-bx-type"):null}if(s=="taskbarflip"||!this.bShown&&(s=="taskbarmanager"||!s)){if(this.bShown){this.Hide()}else{this.Show()}}else if(s=="taskbargroup_title"){BX.onCustomEvent(this,"taskbargroupTitleClick",[i])}else if(s=="taskbarelement"){BX.onCustomEvent(this,"taskbarelementClick",[i])}else if(s=="taskbar_title_but"){BX.onCustomEvent(this,"taskbarTitleClick",[i])}else if(s=="taskbar_top_menu"){BX.onCustomEvent(this,"taskbarMenuClick",[i])}else if(s=="taskbar_search_cancel"){this.pSearchInput.value="";this.TaskbarSearch()}},Show:function(t){if(!this.bShown){this.bShown=true;this.pCont.className="bxhtmled-taskbar-cnt bxhtmled-taskbar-shown"}this.pCont.style.width=this.GetWidth(true)+"px";this.editor.ResizeSceleton();if(t!==false){this.editor.SaveOption("taskbar_shown",1)}},Hide:function(t){if(this.bShown){this.bShown=false;this.pCont.className="bxhtmled-taskbar-cnt bxhtmled-taskbar-hidden"}this.pCont.style.width=this.GetWidth()+"px";this.editor.ResizeSceleton();if(t!==false){this.editor.SaveOption("taskbar_shown",0)}},GetWidth:function(t,e){var i;if(this.bShown){i=t?Math.max(this.width,this.closedWidth+this.MIN_CLOSED_WIDTH):this.width;if(e&&i>e){i=this.width=Math.round(e)}}else{i=this.closedWidth}return i},AddTaskbar:function(t){this.taskbars[t.id]=t;this.pCont.appendChild(t.GetCont());this.pTopCont.appendChild(t.GetTitleCont())},ShowTaskbar:function(t){this.pSearchInput.value="";for(var e in this.taskbars){if(this.taskbars.hasOwnProperty(e)){if(e==t){this.taskbars[e].Activate();this.pSearchInput.placeholder=this.taskbars[e].searchPlaceholder}else{this.taskbars[e].Deactivate()}this.activeTaskbarId=t;this.taskbars[e].ClearSearchResult()}}},GetActiveTaskbar:function(){return this.taskbars[this.activeTaskbarId]},StartResize:function(t){if(!t)t=window.event;var e=t.target||t.srcElement;if(e.getAttribute("data-bx-tsk-split-but")=="Y")return true;this.freezeOnclickHandler=true;var i=this.GetWidth(),s=this.editor.dom.resizerOverlay,o=0,n,a=BX.GetWindowScrollPos(),r=t.clientX+a.scrollLeft,l=this;s.style.display="block";function h(t,e){if(!t)t=window.event;var s=t.clientX+a.scrollLeft;if(r==s)return;o=r-s;n=i+o;if(e){l.width=Math.max(n,l.closedWidth+l.MIN_CLOSED_WIDTH);if(isNaN(l.width)){l.width=l.closedWidth+l.MIN_CLOSED_WIDTH}}else{l.width=n}if(n>l.closedWidth+(e?20:0)){l.Show()}else{l.Hide()}}function d(t){h(t,true);BX.unbind(document,"mousemove",h);BX.unbind(document,"mouseup",d);s.style.display="none";setTimeout(function(){l.freezeOnclickHandler=false},10);BX.PreventDefault(t);l.editor.SaveOption("taskbar_width",l.GetWidth(true))}BX.bind(document,"mousemove",h);BX.bind(document,"mouseup",d)},Resize:function(t,e){var i=parseInt(this.pTopCont.offsetHeight,10);for(var s in this.taskbars){if(this.taskbars.hasOwnProperty(s)&&this.taskbars[s].pTreeCont){this.taskbars[s].pTreeCont.style.height=e-i-42+"px"}}this.pSearchCont.style.width=t+"px";if(!BX.browser.IsDoctype()){this.pSearchAli.style.width=t-20+"px"}var o=this;if(this.resizeTimeout){this.resizeTimeout=clearTimeout(this.resizeTimeout)}this.resizeTimeout=setTimeout(function(){if(parseInt(o.pTopCont.offsetHeight,10)!==i){o.Resize(t,e)}},100)},TaskbarSearch:function(t){var e=this.GetActiveTaskbar(),i=this.pSearchInput.value;if(t&&t.keyCode==this.editor.KEY_CODES["escape"]){i=this.pSearchInput.value=""}if(i.length<2){e.ClearSearchResult()}else{e.Search(i)}}};function e(t){this.editor=t;this.manager=this.editor.taskbarManager;this.searchIndex=[];this._searchResult=[];this._searchResultSect=[];BX.addCustomEvent(this.manager,"taskbargroupTitleClick",BX.proxy(this.OnGroupTitleClick,this));BX.addCustomEvent(this.manager,"taskbarelementClick",BX.proxy(this.OnElementClick,this));BX.addCustomEvent(this.manager,"taskbarTitleClick",BX.proxy(this.OnTitleClick,this));BX.addCustomEvent(this.manager,"taskbarMenuClick",BX.proxy(this.OnMenuClick,this))}e.prototype={GetCont:function(){return this.pTreeCont},GetTitleCont:function(){return this.pTitleCont},BuildSceleton:function(){this.pTitleCont=BX.create("span",{props:{className:"bxhtmled-split-btn"},html:'<span class="bxhtmled-split-btn-l"><span class="bxhtmled-split-btn-bg">'+this.title+'</span></span><span class="bxhtmled-split-btn-r"><span data-bx-type="taskbar_top_menu" data-bx-taskbar="'+this.id+'" class="bxhtmled-split-btn-bg"></span></span>'});this.pTitleCont.setAttribute("data-bx-type","taskbar_title_but");this.pTitleCont.setAttribute("data-bx-taskbar",this.id);this.pTreeCont=BX.create("DIV",{props:{className:"bxhtmled-taskbar-tree-cont"}});this.pTreeInnerCont=this.pTreeCont.appendChild(BX.create("DIV",{props:{className:"bxhtmled-taskbar-tree-inner-cont"}}))},BuildTree:function(t,e){BX.cleanNode(this.pTreeCont);this.treeSectionIndex={};this.BuildTreeSections(t);this.BuildTreeElements(e)},BuildTreeSections:function(t){this.sections=[];for(var e=0;e<t.length;e++){this.BuildSection(t[e])}},GetSectionsTreeInfo:function(){return this.sections},BuildSection:function(t){var e=this.GetSectionContByPath(t.path),i=BX.create("DIV",{props:{className:"bxhtmled-tskbr-sect-outer"}}),s=i.appendChild(BX.create("DIV",{props:{className:"bxhtmled-tskbr-sect"}})),o=s.appendChild(BX.create("SPAN",{props:{className:"bxhtmled-tskbr-sect-icon"}})),n=s.appendChild(BX.create("SPAN",{props:{className:"bxhtmled-tskbr-sect-title"},text:t.title||t.name})),a=i.appendChild(BX.create("DIV",{props:{className:"bxhtmled-tskb-child"}})),r=i.appendChild(BX.create("DIV",{props:{className:"bxhtmled-tskb-child-elements"}}));var l=t.path==""?t.name:t.path+","+t.name;var h=t.path==""?0:1;var d={key:l,children:[],section:t};this.treeSectionIndex[l]={icon:o,outerCont:i,cont:s,childCont:a,elementsCont:r,sect:d};this.GetSectionByPath(t.path).push(d);if(h>0){BX.addClass(s,"bxhtmled-tskbr-sect-"+h);BX.addClass(o,"bxhtmled-tskbr-sect-icon-"+h)}s.setAttribute("data-bx-type","taskbargroup_title");s.setAttribute("data-bx-taskbar",this.id);i.setAttribute("data-bx-type","taskbargroup");i.setAttribute("data-bx-path",l);i.setAttribute("data-bx-taskbar",this.id);e.appendChild(i)},BuildTreeElements:function(t){this.elements=t;for(var e in t){if(t.hasOwnProperty(e)){this.BuildElement(t[e])}}},BuildElement:function(t){var e=this,i=this.GetSectionContByPath(t.key||t.path,true),s=BX.create("DIV",{props:{className:"bxhtmled-tskbr-element"},html:'<span class="bxhtmled-tskbr-element-icon"></span><span class="bxhtmled-tskbr-element-text">'+t.title+"</span>"});var o=s.appendChild(BX.create("IMG",{props:{src:this.editor.util.GetEmptyImage(),className:"bxhtmled-drag"}}));this.HandleElementEx(s,o,t);this.searchIndex.push({content:(t.title+" "+t.name).toLowerCase(),element:s});o.onmousedown=function(t){if(!t){t=window.event}var i=t.target||t.srcElement,s=e.editor.GetBxTag(i);return e.OnElementMouseDownEx(t,i,s)};o.ondblclick=function(t){var i=t.target||t.srcElement,s=e.editor.GetBxTag(i);return e.OnElementDoubleClick(t,i,s)};o.ondragend=function(t){if(!t){t=window.event}e.OnDragEndHandler(t,this)};s.setAttribute("data-bx-type","taskbarelement");i.appendChild(s)},HandleElementEx:function(t){},GetSectionContByPath:function(t,e){if(t==""||!this.treeSectionIndex[t]){return this.pTreeCont}else{return e?this.treeSectionIndex[t].elementsCont:this.treeSectionIndex[t].childCont}},GetSectionByPath:function(t){if(t==""||!this.treeSectionIndex[t]){return this.sections}else{return this.treeSectionIndex[t].sect.children}},ToggleGroup:function(t,e){var i=t.getAttribute("data-bx-path");if(i){var s=this.treeSectionIndex[i];if(!s){return}if(e!==undefined){s.opened=!e}if(s.opened){BX.removeClass(s.cont,"bxhtmled-tskbr-sect-open");BX.removeClass(s.icon,"bxhtmled-tskbr-sect-icon-open");BX.removeClass(s.outerCont,"bxhtmled-tskbr-sect-outer-open");s.childCont.style.display="none";s.elementsCont.style.display="none";s.opened=false}else{BX.addClass(s.cont,"bxhtmled-tskbr-sect-open");BX.addClass(s.icon,"bxhtmled-tskbr-sect-icon-open");BX.addClass(s.outerCont,"bxhtmled-tskbr-sect-outer-open");s.childCont.style.display="block";s.elementsCont.style.display=s.elementsCont.childNodes.length>0?"block":"none";s.opened=true}}},OnDragEndHandler:function(t,e){var i=this;this.editor.skipPasteHandler=true;setTimeout(function(){var t=i.editor.GetIframeElement(e.id);if(t&&t.parentNode){var s=i.editor.util.CheckSurrogateNode(t.parentNode);if(s){i.editor.util.InsertAfter(t,s)}}i.editor.synchro.FullSyncFromIframe();i.editor.skipPasteHandler=false},10)},OnElementMouseDownEx:function(t){return true},OnElementClick:function(t){this.OnElementClickEx();return true},OnElementClickEx:function(){return true},OnElementDoubleClick:function(t,e,i){if(e){var s=e.cloneNode(true);this.editor.Focus();this.editor.selection.InsertNode(s);this.editor.synchro.FullSyncFromIframe()}},OnGroupTitleClick:function(t){if(t&&t.getAttribute("data-bx-taskbar")==this.id){return this.ToggleGroup(t.parentNode)}return true},OnTitleClick:function(t){if(t&&t.getAttribute("data-bx-taskbar")==this.id){return this.manager.ShowTaskbar(this.id)}return true},OnMenuClick:function(t){if(t&&t.getAttribute("data-bx-taskbar")==this.id)return this.ShowMenu(t);return true},Activate:function(){this.pTreeCont.style.display="block";this.bActive=true;return true},Deactivate:function(){this.pTreeCont.style.display="none";this.bActive=false;return true},IsActive:function(){return!!this.bActive},ShowMenu:function(t){var e=this.GetMenuItems();BX.PopupMenu.destroy(this.uniqueId+"_menu");BX.PopupMenu.show(this.uniqueId+"_menu",t,e,{overlay:{opacity:.1},events:{onPopupClose:function(){BX.removeClass(this.bindElement,"bxec-add-more-over")}},offsetLeft:1,zIndex:3005});return true},GetMenuItems:function(){return[]},Search:function(t){this.ClearSearchResult();var e=false,i,s,o,n=this.searchIndex.length;t=BX.util.trim(t.toLowerCase());BX.addClass(this.pTreeCont,"bxhtmled-taskbar-tree-cont-search");BX.addClass(this.manager.pSearchCont,"bxhtmled-search-cont-res");for(o=0;o<n;o++){s=this.searchIndex[o];if(s.content.indexOf(t)!==-1){e=true;BX.addClass(s.element,"bxhtmled-tskbr-search-res");this._searchResult.push(s.element);i=BX.findParent(s.element,function(t){return t.getAttribute&&t.getAttribute("data-bx-type")=="taskbargroup"},this.pTreeCont);while(i){BX.addClass(i,"bxhtmled-tskbr-search-res");this.ToggleGroup(i,true);this._searchResultSect.push(i);i=BX.findParent(i,function(t){return t.getAttribute&&t.getAttribute("data-bx-type")=="taskbargroup"},this.pTreeCont)}}}if(!e){this.manager.pSearchNothingNotice.style.display="block"}},ClearSearchResult:function(){BX.removeClass(this.pTreeCont,"bxhtmled-taskbar-tree-cont-search");BX.removeClass(this.manager.pSearchCont,"bxhtmled-search-cont-res");this.manager.pSearchNothingNotice.style.display="none";var t;if(this._searchResult){for(t=0;t<this._searchResult.length;t++){BX.removeClass(this._searchResult[t],"bxhtmled-tskbr-search-res")}this._searchResult=[]}if(this._searchResultSect){for(t=0;t<this._searchResultSect.length;t++){BX.removeClass(this._searchResultSect[t],"bxhtmled-tskbr-search-res");this.ToggleGroup(this._searchResultSect[t],false)}this._searchResultSect=[]}},GetId:function(){return this.id}};function i(t){i.superclass.constructor.apply(this,arguments);this.id="components";this.title=BX.message("ComponentsTitle");this.templateId=this.editor.templateId;this.uniqueId="taskbar_"+this.editor.id+"_"+this.id;this.searchPlaceholder=BX.message("BXEdCompSearchPlaceHolder");this.Init()}BX.extend(i,e);i.prototype.Init=function(){this.BuildSceleton();var t=this.editor.components.GetList();this.BuildTree(t.groups,t.items)};i.prototype.HandleElementEx=function(t,e,i){this.editor.SetBxTag(e,{tag:"component_icon",params:i});if(i.complex=="Y"){i.className="bxhtmled-surrogate-green";BX.addClass(t,"bxhtmled-tskbr-element-green");t.title=BX.message("BXEdComplexComp")}};i.prototype.OnElementMouseDownEx=function(t,e,i){if(!i||i.tag!=="component_icon"){return false}this.editor.components.LoadParamsList({name:i.params.name})};i.prototype.GetMenuItems=function(){var t=this;return[{text:BX.message("RefreshTaskbar"),title:BX.message("RefreshTaskbar"),className:"",onclick:function(){t.editor.componentsTaskbar.ClearSearchResult();t.editor.components.ReloadList();BX.PopupMenu.destroy(t.uniqueId+"_menu")}}]};function s(t,e){this.editor=t;this.id=e.id;this.params=e;this.className="bxhtmled-dialog"+(e.className?" "+e.className:"");this.zIndex=e.zIndex||3008;this.firstFocus=false;this.Init()}s.prototype={Init:function(){var t=this,e={title:this.params.title||this.params.name||"",width:this.params.width||600,resizable:false};if(this.params.resizable){e.resizable=true;e.min_width=this.params.min_width||400;e.min_height=this.params.min_height||250;e.resize_id=this.params.resize_id||this.params.id+"_res"}this.oDialog=new BX.CDialog(e);e.height=this.params.height||false;BX.addCustomEvent(this.oDialog,"onWindowResize",BX.proxy(this.OnResize,this));BX.addCustomEvent(this.oDialog,"onWindowResizeFinished",BX.proxy(this.OnResizeFinished,this));BX.addClass(this.oDialog.PARTS.CONTENT,this.className);if(!e.height){this.oDialog.PARTS.CONTENT_DATA.style.height=null}this.oDialog.SetButtons([new BX.CWindowButton({title:BX.message("DialogSave"),className:"adm-btn-save",action:function(){BX.onCustomEvent(t,"OnDialogSave");t.oDialog.Close()}}),this.oDialog.btnCancel]);BX.addCustomEvent(this.oDialog,"onWindowUnRegister",function(){BX.unbind(window,"keydown",BX.proxy(t.OnKeyDown,t));t.dialogShownTimeout=setTimeout(function(){t.editor.dialogShown=false},300)})},Show:function(){var t=this;this.editor.dialogShown=true;if(this.dialogShownTimeout){this.dialogShownTimeout=clearTimeout(this.dialogShownTimeout)}this.oDialog.Show();this.oDialog.DIV.style.zIndex=this.zIndex;this.oDialog.OVERLAY.style.zIndex=this.zIndex-2;var e=parseInt(this.oDialog.DIV.style.top)-180;this.oDialog.DIV.style.top=(e>50?e:50)+"px";BX.bind(window,"keydown",BX.proxy(this.OnKeyDown,this));setTimeout(function(){if(BX.browser.IsOpera())t.oDialog.Move(1,1);t.oDialog.__resizeOverlay();if(t.firstFocus){BX.focus(t.firstFocus);if(t.selectFirstFocus)t.firstFocus.select()}},100)},BuildTabControl:function(t,e){var i,s=BX.create("DIV",{props:{className:"bxhtmled-dlg-tabs-wrap"}}),o=BX.create("DIV",{props:{className:"bxhtmled-dlg-cont-wrap"}});for(i=0;i<e.length;i++){e[i].tab=s.appendChild(BX.create("SPAN",{props:{className:"bxhtmled-dlg-tab"+(i==0?" bxhtmled-dlg-tab-active":"")},attrs:{"data-bx-dlg-tab-ind":i.toString()},text:e[i].name}));e[i].cont=o.appendChild(BX.create("DIV",{props:{className:"bxhtmled-dlg-cont"},style:{display:i==0?"":"none"}}))}BX.bind(s,"click",function(t){var s,o=t.target||t.srcElement;if(o&&o.getAttribute){s=parseInt(o.getAttribute("data-bx-dlg-tab-ind"));if(!isNaN(s)){for(i=0;i<e.length;i++){if(i==s){e[i].cont.style.display="";BX.addClass(e[i].tab,"bxhtmled-dlg-tab-active")}else{e[i].cont.style.display="none";BX.removeClass(e[i].tab,"bxhtmled-dlg-tab-active")}}}}});t.appendChild(s);t.appendChild(o);return{cont:t,tabsWrap:s,contWrap:o,tabs:e}},OnKeyDown:function(t){if(t.keyCode==13&&this.closeByEnter!==false){var e=t.target||t.srcElement;if(e&&e.nodeName!=="TEXTAREA"){this.oDialog.PARAMS.buttons[0].emulate()}}},SetContent:function(t){return this.oDialog.SetContent(t)},SetTitle:function(t){return this.oDialog.SetTitle(t)},OnResize:function(){},OnResizeFinished:function(){},GetContentSize:function(){return{width:this.oDialog.PARTS.CONTENT_DATA.offsetWidth,height:this.oDialog.PARTS.CONTENT_DATA.offsetHeight}},Save:function(){if(this.savedRange){this.editor.selection.SetBookmark(this.savedRange)}if(this.action&&this.editor.action.IsSupported(this.action)){this.editor.action.Exec(this.action,this.GetValues())}},Close:function(){if(this.IsOpen()){this.oDialog.Close()}},IsOpen:function(){return this.oDialog.isOpen},DisableKeyCheck:function(){this.closeByEnter=false;BX.WindowManager.disableKeyCheck()},EnableKeyCheck:function(){var t=this;setTimeout(function(){t.closeByEnter=true;BX.WindowManager.enableKeyCheck()},200)},AddTableRow:function(t,e){var i,s,o;i=t.insertRow(-1);s=i.insertCell(-1);s.className="bxhtmled-left-c";if(e&&e.label){s.appendChild(BX.create("LABEL",{props:{className:e.required?"bxhtmled-req":""},text:e.label})).setAttribute("for",e.id)}o=i.insertCell(-1);o.className="bxhtmled-right-c";return{row:i,leftCell:s,rightCell:o}},SetValues:BX.DoNothing,GetValues:BX.DoNothing};function o(t){this.editor=t;BX.addCustomEvent(this.editor,"OnIframeContextMenu",BX.delegate(this.Show,this));this.Init()}o.prototype={Init:function(){var t=this,e={TEXT:BX.message("ContMenuDefProps"),ACTION:function(){t.editor.selection.SetBookmark(t.savedRange);t.editor.GetDialog("Default").Show(false,t.savedRange);t.Hide()}};this.items={php:[{TEXT:BX.message("BXEdContMenuPhpCode"),ACTION:function(){var e=t.GetTargetItem();if(e&&e.php){t.editor.GetDialog("Source").Show(e.php.bxTag)}t.Hide()}}],anchor:[{TEXT:BX.message("BXEdEditAnchor"),ACTION:function(){var e=t.GetTargetItem();if(e&&e.anchor){t.editor.GetDialog("Anchor").Show(e.anchor.bxTag)}t.Hide()}}],javascript:[{TEXT:BX.message("BXEdContMenuJavascript"),ACTION:function(){var e=t.GetTargetItem();if(e&&e.javascript){t.editor.GetDialog("Source").Show(e.javascript.bxTag)}t.Hide()}}],htmlcomment:[{TEXT:BX.message("BXEdContMenuHtmlComment"),ACTION:function(){var e=t.GetTargetItem();if(e&&e.htmlcomment){t.editor.GetDialog("Source").Show(e.htmlcomment.bxTag)}t.Hide()}}],iframe:[{TEXT:BX.message("BXEdContMenuIframe"),ACTION:function(){var e=t.GetTargetItem();if(e&&e.iframe){t.editor.GetDialog("Source").Show(e.iframe.bxTag)}t.Hide()}}],style:[{TEXT:BX.message("BXEdContMenuStyle"),ACTION:function(){var e=t.GetTargetItem();if(e&&e.style){t.editor.GetDialog("Source").Show(e.style.bxTag)}t.Hide()}}],object:[{TEXT:BX.message("BXEdContMenuObject"),ACTION:function(){var e=t.GetTargetItem();if(e&&e.object){t.editor.GetDialog("Source").Show(e.object.bxTag)}t.Hide()}}],component:[{TEXT:BX.message("BXEdContMenuComponent"),ACTION:function(){var e=t.GetTargetItem();if(e&&e.component){t.editor.components.ShowPropertiesDialog(e.component.bxTag.params,t.editor.GetBxTag(e.component.bxTag.surrogateId))}t.Hide()}},{TEXT:BX.message("BXEdContMenuComponentRemove"),ACTION:function(){var e=t.GetTargetItem();if(e&&e.component){BX.remove(e.component.element)}t.Hide()}}],printbreak:[{TEXT:BX.message("NodeRemove"),ACTION:function(e){var i=t.GetTargetItem("printbreak");if(i&&i.element){t.editor.selection.RemoveNode(i.element)}t.Hide()}}],video:[{TEXT:BX.message("BXEdVideoProps"),bbMode:true,ACTION:function(){var e=t.GetTargetItem("video");if(e){t.editor.GetDialog("Video").Show(e.bxTag)}t.Hide()}},{TEXT:BX.message("BXEdVideoDel"),bbMode:true,ACTION:function(e){var i=t.GetTargetItem("video");if(i&&i.element){t.editor.selection.RemoveNode(i.element)}t.Hide()}}],smile:[],A:[{TEXT:BX.message("ContMenuLinkEdit"),bbMode:true,ACTION:function(){var e=t.GetTargetItem("A");if(e){t.editor.GetDialog("Link").Show([e],this.savedRange)}t.Hide()}},{TEXT:BX.message("ContMenuLinkDel"),bbMode:true,ACTION:function(){var e=t.GetTargetItem("A");if(e&&t.editor.action.IsSupported("removeLink")){t.editor.action.Exec("removeLink",[e])}t.Hide()}}],IMG:[{TEXT:BX.message("ContMenuImgEdit"),bbMode:true,ACTION:function(){var e=t.GetTargetItem("IMG");if(e){t.editor.GetDialog("Image").Show([e],t.savedRange)}t.Hide()}},{TEXT:BX.message("ContMenuImgDel"),bbMode:true,ACTION:function(){var e=t.GetTargetItem("IMG");if(e){t.editor.selection.RemoveNode(e)}t.Hide()}}],DIV:[{TEXT:BX.message("ContMenuCleanDiv"),title:BX.message("ContMenuCleanDiv_Title"),ACTION:function(){var e=t.GetTargetItem("DIV");if(e){t.editor.On("OnHtmlContentChangedByControl");t.editor.util.ReplaceWithOwnChildren(e);t.editor.synchro.FullSyncFromIframe()}t.Hide()}},e],TABLE:[{TEXT:BX.message("BXEdTableInsertMenu"),HIDE_ITEM:function(){var e=t.editor.action.actions.tableOperation.getSelectedCells(t.savedRange,t.GetTargetItem("TABLE"));return!e||e.length!=1},MENU:[{TEXT:BX.message("BXEdTableInsColLeft"),ACTION:function(){t.editor.action.Exec("tableOperation",{actionType:"insertColumnLeft",tableNode:t.GetTargetItem("TABLE"),range:t.savedRange});t.Hide()}},{TEXT:BX.message("BXEdTableInsColRight"),ACTION:function(){t.editor.action.Exec("tableOperation",{actionType:"insertColumnRight",tableNode:t.GetTargetItem("TABLE"),range:t.savedRange});t.Hide()}},{TEXT:BX.message("BXEdTableInsRowUpper"),ACTION:function(){t.editor.action.Exec("tableOperation",{actionType:"insertRowUpper",tableNode:t.GetTargetItem("TABLE"),range:t.savedRange});t.Hide()}},{TEXT:BX.message("BXEdTableInsRowLower"),ACTION:function(){t.editor.action.Exec("tableOperation",{actionType:"insertRowLower",tableNode:t.GetTargetItem("TABLE"),range:t.savedRange});t.Hide()}},{TEXT:BX.message("BXEdTableInsCellBefore"),ACTION:function(){t.editor.action.Exec("tableOperation",{actionType:"insertCellLeft",tableNode:t.GetTargetItem("TABLE"),range:t.savedRange});t.Hide()}},{TEXT:BX.message("BXEdTableInsCellAfter"),ACTION:function(){t.editor.action.Exec("tableOperation",{actionType:"insertCellRight",tableNode:t.GetTargetItem("TABLE"),range:t.savedRange});t.Hide()}}]},{TEXT:BX.message("BXEdTableRemoveMenu"),MENU:[{TEXT:BX.message("BXEdTableDelCol"),ACTION:function(){t.editor.action.Exec("tableOperation",{actionType:"removeColumn",tableNode:t.GetTargetItem("TABLE"),range:t.savedRange});t.Hide()},HIDE_ITEM:function(){var e=t.editor.action.actions.tableOperation.getSelectedCells(t.savedRange,t.GetTargetItem("TABLE"));return!e||e.length!=1}},{TEXT:BX.message("BXEdTableDelRow"),ACTION:function(){t.editor.action.Exec("tableOperation",{actionType:"removeRow",tableNode:t.GetTargetItem("TABLE"),range:t.savedRange});t.Hide()},HIDE_ITEM:function(){var e=t.editor.action.actions.tableOperation.getSelectedCells(t.savedRange,t.GetTargetItem("TABLE"));return!e||e.length!=1}},{TEXT:BX.message("BXEdTableDellCell"),ACTION:function(){t.editor.action.Exec("tableOperation",{actionType:"removeCell",tableNode:t.GetTargetItem("TABLE"),range:t.savedRange});t.Hide()},HIDE_ITEM:function(){var e=t.editor.action.actions.tableOperation.getSelectedCells(t.savedRange,t.GetTargetItem("TABLE"));return!e||e.length!=1}},{TEXT:BX.message("BXEdTableDellSelectedCells"),ACTION:function(){t.editor.action.Exec("tableOperation",{actionType:"removeSelectedCells",tableNode:t.GetTargetItem("TABLE"),range:t.savedRange});t.Hide()},HIDE_ITEM:function(){var e=t.editor.action.actions.tableOperation.getSelectedCells(t.savedRange,t.GetTargetItem("TABLE"));return!e||e.length===1}}]},{TEXT:BX.message("BXEdTableMergeMenu"),MENU:[{TEXT:BX.message("BXEdTableMergeSelectedCells"),ACTION:function(){t.editor.action.Exec("tableOperation",{actionType:"mergeSelectedCells",tableNode:t.GetTargetItem("TABLE"),range:t.savedRange});t.Hide()},HIDE_ITEM:function(){if(!t.savedRange.collapsed){return!t.editor.action.actions.tableOperation.canBeMerged(false,t.savedRange,t.GetTargetItem("TABLE"))}return true}},{TEXT:BX.message("BXEdTableMergeRight"),ACTION:function(){t.editor.action.Exec("tableOperation",{actionType:"mergeRightCell",tableNode:t.GetTargetItem("TABLE"),range:t.savedRange});t.Hide()},HIDE_ITEM:function(){return!t.editor.action.actions.tableOperation.canBeMergedWithRight(t.savedRange,t.GetTargetItem("TABLE"))}},{TEXT:BX.message("BXEdTableMergeBottom"),ACTION:function(){t.editor.action.Exec("tableOperation",{actionType:"mergeBottomCell",tableNode:t.GetTargetItem("TABLE"),range:t.savedRange});t.Hide()},HIDE_ITEM:function(){return!t.editor.action.actions.tableOperation.canBeMergedWithBottom(t.savedRange,t.GetTargetItem("TABLE"))}},{TEXT:BX.message("BXEdTableMergeRowCells"),ACTION:function(){t.editor.action.Exec("tableOperation",{actionType:"mergeRow",tableNode:t.GetTargetItem("TABLE"),range:t.savedRange});t.Hide()},HIDE_ITEM:function(){var e=t.editor.action.actions.tableOperation.getSelectedCells(t.savedRange,t.GetTargetItem("TABLE"));return!e||e.length>1}},{TEXT:BX.message("BXEdTableMergeColCells"),ACTION:function(){t.editor.action.Exec("tableOperation",{actionType:"mergeColumn",tableNode:t.GetTargetItem("TABLE"),range:t.savedRange});t.Hide()},HIDE_ITEM:function(){var e=t.editor.action.actions.tableOperation.getSelectedCells(t.savedRange,t.GetTargetItem("TABLE"));return!e||e.length>1}}]},{TEXT:BX.message("BXEdTableSplitMenu"),HIDE_ITEM:function(){var e=t.editor.action.actions.tableOperation.getSelectedCells(t.savedRange,t.GetTargetItem("TABLE"));return!e||e.length!=1},MENU:[{TEXT:BX.message("BXEdTableSplitCellHor"),ACTION:function(){t.editor.action.Exec("tableOperation",{actionType:"splitHorizontally",tableNode:t.GetTargetItem("TABLE"),range:t.savedRange});t.Hide()}},{TEXT:BX.message("BXEdTableSplitCellVer"),ACTION:function(){t.editor.action.Exec("tableOperation",{actionType:"splitVertically",tableNode:t.GetTargetItem("TABLE"),range:t.savedRange});t.Hide()}}]},{SEPARATOR:true},{TEXT:BX.message("BXEdTableTableCellProps"),ACTION:function(){var e=t.GetTargetItem("TABLE");if(e){var i=t.editor.action.actions.tableOperation.getSelectedCells(t.savedRange,t.GetTargetItem("TABLE"));t.editor.GetDialog("Default").Show(i,t.savedRange)}t.Hide()},HIDE_ITEM:function(){var e=t.editor.action.actions.tableOperation.getSelectedCells(t.savedRange,t.GetTargetItem("TABLE"));return!e||e.length!=1}},{TEXT:BX.message("BXEdTableTableProps"),ACTION:function(){var e=t.GetTargetItem("TABLE");if(e){t.editor.GetDialog("Table").Show([e],t.savedRange)}t.Hide()}},{TEXT:BX.message("BXEdTableDeleteTable"),bbMode:true,ACTION:function(){var e=t.GetTargetItem("TABLE");if(e){t.editor.selection.RemoveNode(e)}t.Hide()}}],DEFAULT:[e]}},Show:function(t,e){this.savedRange=this.editor.selection.GetBookmark();this.Hide();this.editor.contextMenuShown=true;if(this.contextMenuShownTimeout){this.contextMenuShownTimeout=clearTimeout(this.contextMenuShownTimeout)}this.nodes=[];this.tagIndex={};var i,s,o,n,a,r,l,h=[],d=20,c=0,u=e,p;this.targetItems={};while(true){if(u.nodeName&&u.nodeName.toUpperCase()!="BODY"){if(u.nodeType!=3){i=this.editor.GetBxTag(u);if(i&&i.tag=="surrogate_dd"){var m=this.editor.GetBxTag(i.params.origId);u=this.editor.GetIframeElement(m.id);this.PushTargetItem(m.tag,{element:u,bxTag:m});this.nodes=[u];this.tagIndex[m.tag]=0;c=0;u=u.parentNode;continue}else if(i&&i.tag&&this.items[i.tag]){this.nodes=[u];this.PushTargetItem(i.tag,{element:u,bxTag:i.tag});this.nodes=[u];this.tagIndex[i.tag]=0;c=0;u=u.parentNode;continue}p=u.nodeName;this.PushTargetItem(p,u);this.nodes.push(u);this.tagIndex[p]=this.nodes.length-1}c++}if(!u||u.nodeName&&u.nodeName.toUpperCase()=="BODY"||c>=d){break}u=u.parentNode}for(s in this.items){if(this.items.hasOwnProperty(s)&&this.tagIndex[s]!=undefined){if(h.length>0){h.push({SEPARATOR:true})}for(o=0;o<this.items[s].length;o++){if(typeof this.items[s][o].HIDE_ITEM=="function"&&this.items[s][o].HIDE_ITEM()===true)continue;if(this.editor.bbCode&&!this.items[s][o].bbMode)continue;if(this.items[s][o].MENU){l=BX.clone(this.items[s][o]);a=[];for(n=0;n<l.MENU.length;n++){r=l.MENU[n];if(typeof r.HIDE_ITEM=="function"&&r.HIDE_ITEM()===true)continue;if(this.editor.bbCode&&!r.bbMode)continue;a.push(r)}if(a.length===0)continue;l.MENU=a;h.push(l)}else{h.push(this.items[s][o])}}}}if(h.length==0){var f=this.items["DEFAULT"];if(!this.editor.bbCode||f.bbMode){for(o=0;o<f.length;o++){h.push(f[o])}}}var b=t.clientX,C=t.clientY;if(!this.dummyTarget){this.dummyTarget=this.editor.dom.iframeCont.appendChild(BX.create("DIV",{props:{className:"bxhtmled-dummy-target"}}))}this.dummyTarget.style.left=b+"px";this.dummyTarget.style.top=C+"px";this.dummyTarget.style.zIndex="2002";if(h.length>0){this.OPENER=new BX.COpener({DIV:this.dummyTarget,MENU:h,TYPE:"click",ACTIVE_CLASS:"adm-btn-active",CLOSE_ON_CLICK:true});this.OPENER.Open();this.OPENER.GetMenu().DIV.style.zIndex="3005";this.isOpened=true;BX.addCustomEvent(this.editor,"OnIframeClick",BX.proxy(this.Hide,this));BX.addCustomEvent(this.editor,"OnIframeKeyup",BX.proxy(this.CheckEscapeClose,this));return BX.PreventDefault(t)}},Hide:function(){if(this.OPENER){var t=this;this.contextMenuShownTimeout=setTimeout(function(){t.editor.contextMenuShown=false},300);this.OPENER.bMenuInit=true;this.OPENER.Close();this.isOpened=false;BX.removeCustomEvent(this.editor,"OnIframeClick",BX.proxy(this.Hide,this));BX.removeCustomEvent(this.editor,"OnIframeKeyup",BX.proxy(this.CheckEscapeClose,this))}},CheckEscapeClose:function(t,e){if(e==this.editor.KEY_CODES["escape"])this.Hide()},GetTargetItem:function(t){return t?this.targetItems[t]||null:this.targetItems},PushTargetItem:function(t,e){if(!this.targetItems[t])this.targetItems[t]=e}};function n(t,e){this.editor=t;this.pCont=t.dom.toolbar;this.controls={};this.bCompact=false;this.topControls=e;this.showMoreButton=false;this.shown=true;this.height=34;this.Init()}n.prototype={Init:function(){this.BuildControls();BX.addCustomEvent(this.editor,"OnIframeFocus",BX.delegate(this.EnableWysiwygButtons,this));BX.addCustomEvent(this.editor,"OnTextareaFocus",BX.delegate(this.DisableWysiwygButtons,this))},BuildControls:function(){BX.cleanNode(this.pCont);var t,e,i,s,o=this.GetControlsMap(),n={left:this.pCont.appendChild(BX.create("span",{props:{className:"bxhtmled-top-bar-left-wrap"},style:{display:"none"}})),main:this.pCont.appendChild(BX.create("span",{props:{className:"bxhtmled-top-bar-wrap"},style:{display:"none"}})),right:this.pCont.appendChild(BX.create("span",{props:{className:"bxhtmled-top-bar-right-wrap"},style:{display:"none"}})),hidden:this.pCont.appendChild(BX.create("span",{props:{className:"bxhtmled-top-bar-hidden-wrap"}}))};this.hiddenWrap=n.hidden;this.editor.normalWidth=this.editor.NORMAL_WIDTH;for(t=0;t<o.length;t++){if(o[t].hidden){o[t].wrap="hidden";this.showMoreButton=true}else if(o[t].checkWidth&&o[t].offsetWidth){this.editor.normalWidth+=o[t].offsetWidth}e=n[o[t].wrap||"main"];if(!e){e=BX(o[t].wrap);if(e){n[o[t].wrap]=e}else{e=n["main"]}}if(e.style.display=="none")e.style.display="";if(o[t].separator){e.appendChild(this.GetSeparator())}else if(this.topControls[o[t].id]){if(!this.controls[o[t].id]){this.controls[o[t].id]=new this.topControls[o[t].id](this.editor,e)}else{s=this.controls[o[t].id].GetPopupBindCont?this.controls[o[t].id].GetPopupBindCont():this.controls[o[t].id].GetCont();if(this.controls[o[t].id].CheckBeforeShow&&!this.controls[o[t].id].CheckBeforeShow())continue;if(this.controls.More&&(this.bCompact&&!o[t].compact||o[t].hidden)){if(!i){i=this.controls.More.GetPopupCont()}i.appendChild(s)}else{e.appendChild(s)}}}}for(t in n){if(n.hasOwnProperty(t)&&t!=="main"&&t!=="left"&&t!=="right"&&t!=="hidden"&&n[t].getAttribute("data-bx-check-command")!=="N"){n[t].setAttribute("data-bx-check-command","N");BX.bind(n[t],"click",BX.proxy(function(t){this.editor.CheckCommand(t.target||t.srcElement)},this))}}},GetControlsMap:function(){if(this.controlsMap)return this.controlsMap;

var t=this.editor.config.controlsMap;if(!t){t=[{id:"ChangeView",wrap:"left",compact:true,sort:10},{id:"Undo",compact:false,sort:20},{id:"Redo",compact:false,sort:30},{id:"StyleSelector",compact:true,sort:40},{id:"FontSelector",compact:false,sort:50},{id:"FontSize",compact:false,sort:60},{separator:true,compact:false,sort:70},{id:"Bold",compact:true,sort:80},{id:"Italic",compact:true,sort:90},{id:"Underline",compact:true,sort:100},{id:"Strikeout",compact:true,sort:110},{id:"RemoveFormat",compact:true,sort:120},{id:"Color",compact:true,sort:130},{separator:true,compact:false,sort:140},{id:"OrderedList",compact:true,sort:150},{id:"UnorderedList",compact:true,sort:160},{id:"IndentButton",compact:true,sort:170},{id:"OutdentButton",compact:true,sort:180},{id:"AlignList",compact:true,sort:190},{separator:true,compact:false,sort:200},{id:"InsertLink",compact:true,sort:210},{id:"InsertImage",compact:true,sort:220},{id:"InsertVideo",compact:true,sort:230},{id:"InsertAnchor",compact:false,sort:240},{id:"InsertTable",compact:false,sort:250},{id:"InsertChar",compact:false,hidden:true,sort:260},{id:"PrintBreak",compact:false,hidden:true,sort:270},{id:"PageBreak",compact:false,hidden:true,sort:275},{id:"Spellcheck",compact:false,hidden:true,sort:280},{id:"Sub",compact:false,hidden:true,sort:310},{id:"Sup",compact:false,hidden:true,sort:320},{id:"TemplateSelector",compact:false,sort:330},{id:"Fullscreen",compact:true,sort:340},{id:"More",compact:true,sort:400},{id:"Settings",wrap:"right",compact:true,sort:500}]}this.editor.On("GetControlsMap",[t]);t=t.sort(function(t,e){return t.sort-e.sort});this.controlsMap=t;return t},GetSeparator:function(){return BX.create("span",{props:{className:"bxhtmled-top-bar-separator"}})},GetHeight:function(){var t=0;if(this.shown){if(!this.height)this.height=parseInt(this.editor.dom.toolbarCont.offsetHeight);t=this.height}return t},DisableWysiwygButtons:function(t){t=t!==false;for(var e in this.controls){if(this.controls.hasOwnProperty(e)&&typeof this.controls[e].Disable=="function"&&this.controls[e].disabledForTextarea!==false)this.controls[e].Disable(t)}},EnableWysiwygButtons:function(){this.DisableWysiwygButtons(false)},AdaptControls:function(t){var e=t<this.editor.normalWidth;if(this.controls.More){if(e||this.showMoreButton){this.controls.More.GetCont().style.display=""}else{this.controls.More.GetCont().style.display="none"}this.controls.More.Close()}if(!e&&this.showMoreButton){var i=this.controls.More.GetPopupCont();while(this.hiddenWrap.firstChild){i.appendChild(this.hiddenWrap.firstChild)}}if(this.bCompact!=e){this.bCompact=e;this.BuildControls()}},Hide:function(){this.shown=false;this.editor.dom.toolbarCont.style.display="none";this.editor.ResizeSceleton()},Show:function(){this.shown=true;this.editor.dom.toolbarCont.style.display="";this.editor.ResizeSceleton()},IsShown:function(){return this.shown}};function a(t){this.editor=t;this.bShown=false;this.pCont=t.dom.navCont;this.controls={};this.height=28;this.Init()}a.prototype={Init:function(){BX.addCustomEvent(this.editor,"OnIframeMouseDown",BX.proxy(this.OnIframeMousedown,this));BX.addCustomEvent(this.editor,"OnIframeKeyup",BX.proxy(this.OnIframeKeyup,this));BX.addCustomEvent(this.editor,"OnTextareaFocus",BX.delegate(this.Disable,this));BX.addCustomEvent(this.editor,"OnHtmlContentChangedByControl",BX.delegate(this.OnIframeKeyup,this));BX.bind(this.pCont,"click",BX.delegate(this.ShowMenu,this));var t=this;this.items={php:function(e,i){t.editor.GetDialog("Source").Show(i)},anchor:function(e,i){t.editor.GetDialog("Anchor").Show(i)},javascript:function(e,i){t.editor.GetDialog("Source").Show(i)},htmlcomment:function(e,i){t.editor.GetDialog("Source").Show(i)},iframe:function(e,i){t.editor.GetDialog("Source").Show(i)},style:function(e,i){t.editor.GetDialog("Source").Show(i)},video:function(e,i){t.editor.GetDialog("Video").Show(i)},component:function(e,i){t.editor.components.ShowPropertiesDialog(i.params,t.editor.GetBxTag(i.surrogateId))},printbreak:false,A:function(e){t.editor.GetDialog("Link").Show([e])},IMG:function(e){t.editor.GetDialog("Image").Show([e])},TABLE:function(e){t.editor.GetDialog("Table").Show([e])},DEFAULT:function(e){t.editor.GetDialog("Default").Show([e])}}},Show:function(t){this.bShown=t=t!==false;this.pCont.style.display=t?"block":"none"},GetHeight:function(){if(!this.bShown)return 0;if(!this.height)this.height=parseInt(this.pCont.offsetHeight);return this.height},OnIframeMousedown:function(t,e,i){this.BuildNavi(e)},OnIframeKeyup:function(t,e,i){this.BuildNavi(i)},BuildNavi:function(t){BX.cleanNode(this.pCont);if(!t){t=this.editor.GetIframeDoc().body}this.nodeIndex=[];var e,i,s;while(t){if(t.nodeType!=3){s=this.editor.GetBxTag(t);if(s.tag){if(s.tag=="surrogate_dd"){t=t.parentNode;continue}BX.cleanNode(this.pCont);this.nodeIndex=[];i=s.name||s.tag}else{i=t.nodeName}e=BX.create("SPAN",{props:{className:"bxhtmled-nav-item"},text:i});e.setAttribute("data-bx-node-ind",this.nodeIndex.length.toString());this.nodeIndex.push({node:t,bxTag:s.tag});if(this.pCont.firstChild){this.pCont.insertBefore(e,this.pCont.firstChild);if(!this.AdjustSize()){break}}else{this.pCont.appendChild(e)}}if(t.nodeName&&t.nodeName.toUpperCase()=="BODY"){break}t=t.parentNode}this.AdjustSize()},AdjustSize:function(){if(this.pCont.lastChild&&this.pCont.lastChild.offsetTop>0){BX.remove(this.pCont.firstChild);return false}return true},ShowMenu:function(t){if(!this.nodeIndex){return}var e=this,i,s,o;if(t.target){o=t.target}else if(t.srcElement){o=t.srcElement}if(o.nodeType==3){o=o.parentNode}if(o){i=o.getAttribute("data-bx-node-ind");if(!this.nodeIndex[i]){o=BX.findParent(o,function(t){return t==e.pCont||t.getAttribute&&t.getAttribute("data-bx-node-ind")>=0},this.pCont);i=o.getAttribute("data-bx-node-ind")}if(this.nodeIndex[i]){var n="bx_node_nav_"+Math.round(Math.random()*1e9);s=this.nodeIndex[i].node;var a=[];if(s.nodeName&&s.nodeName.toUpperCase()!="BODY"){if(!this.nodeIndex[i].bxTag||!this.editor.phpParser.surrogateTags[this.nodeIndex[i].bxTag]){a.push({text:BX.message("NodeSelect"),title:BX.message("NodeSelect"),className:"",onclick:function(){e.editor.action.Exec("selectNode",s);this.popupWindow.close();this.popupWindow.destroy()}})}a.push({text:BX.message("NodeRemove"),title:BX.message("NodeRemove"),className:"",onclick:function(){if(s&&s.parentNode){e.BuildNavi(s.parentNode);e.editor.selection.RemoveNode(s)}this.popupWindow.close();this.popupWindow.destroy()}});var r=!(this.nodeIndex[i]&&this.nodeIndex[i].bxTag&&this.items[this.nodeIndex[i].bxTag]==false);if(r){a.push({text:BX.message("NodeProps"),title:BX.message("NodeProps"),className:"",onclick:function(){e.ShowNodeProperties(s);this.popupWindow.close();this.popupWindow.destroy()}})}}else{a=[{text:BX.message("NodeSelectBody"),title:BX.message("NodeSelectBody"),className:"",onclick:function(){e.editor.iframeView.CheckContentLastChild();e.editor.action.Exec("selectNode",s);e.editor.Focus();this.popupWindow.close();this.popupWindow.destroy()}},{text:BX.message("NodeRemoveBodyContent"),title:BX.message("NodeRemoveBodyContent"),className:"",onclick:function(){e.BuildNavi(s);e.editor.On("OnHtmlContentChangedByControl");e.editor.iframeView.Clear();e.editor.util.Refresh(s);e.editor.synchro.FullSyncFromIframe();e.editor.Focus();this.popupWindow.close();this.popupWindow.destroy()}}]}BX.PopupMenu.show(n+"_menu",o,a,{overlay:{opacity:1},events:{onPopupClose:function(){}},offsetLeft:1,zIndex:4e3,bindOptions:{position:"top"}})}}},ShowNodeProperties:function(t){var e,i;if(t.nodeName&&t.nodeType==1){e=this.editor.GetBxTag(t);i=e.tag?e.tag:t.nodeName;if(this.items[i]&&typeof this.items[i]=="function"){this.items[i](t,e)}else{this.items["DEFAULT"](t,e)}}},Disable:function(){this.BuildNavi(false)},Enable:function(){}};function r(t,e){this.editor=t;this.id="bxeditor_overlay"+this.editor.id;this.zIndex=e&&e.zIndex?e.zIndex:3001}r.prototype={Create:function(){this.bCreated=true;this.bShown=false;var t=BX.GetWindowScrollSize();this.pWnd=document.body.appendChild(BX.create("DIV",{props:{id:this.id,className:"bxhtmled-overlay"},style:{zIndex:this.zIndex,width:t.scrollWidth+"px",height:t.scrollHeight+"px"}}));this.pWnd.ondrag=BX.False;this.pWnd.onselectstart=BX.False},Show:function(t){if(!this.bCreated)this.Create();this.bShown=true;if(this.shownTimeout){this.shownTimeout=clearTimeout(this.shownTimeout)}var e=BX.GetWindowScrollSize();this.pWnd.style.display="block";this.pWnd.style.width=e.scrollWidth+"px";this.pWnd.style.height=e.scrollHeight+"px";if(!t){t={}}this.pWnd.style.zIndex=t.zIndex||this.zIndex;BX.bind(window,"resize",BX.proxy(this.Resize,this));return this.pWnd},Hide:function(){if(!this.bShown){return}var t=this;t.shownTimeout=setTimeout(function(){t.bShown=false},300);this.pWnd.style.display="none";BX.unbind(window,"resize",BX.proxy(this.Resize,this));this.pWnd.onclick=null},Resize:function(){if(this.bCreated){var t=BX.GetWindowScrollSize();this.pWnd.style.width=t.scrollWidth+"px";this.pWnd.style.height=t.scrollHeight+"px"}}};function l(t){this.editor=t;this.className="bxhtmled-top-bar-btn";this.activeClassName="bxhtmled-top-bar-btn-active";this.disabledClassName="bxhtmled-top-bar-btn-disabled";this.checkableAction=true;this.disabledForTextarea=true}l.prototype={Create:function(){this.pCont=BX.create("SPAN",{props:{className:this.className,title:this.title||""},html:"<i></i>"});BX.bind(this.pCont,"click",BX.delegate(this.OnClick,this));BX.bind(this.pCont,"mousedown",BX.delegate(this.OnMouseDown,this));BX.bind(this.pCont,"dblclick",function(t){return BX.PreventDefault(t)});if(this.action){this.pCont.setAttribute("data-bx-type","action");this.pCont.setAttribute("data-bx-action",this.action);if(this.value)this.pCont.setAttribute("data-bx-value",this.value);if(this.checkableAction){this.editor.RegisterCheckableAction(this.action,{action:this.action,control:this,value:this.value})}}},GetCont:function(){return this.pCont},Check:function(t){if(t==this.checked||this.disabled)return;this.checked=t;if(this.checked){BX.addClass(this.pCont,this.activeClassName)}else{BX.removeClass(this.pCont,this.activeClassName)}},Disable:function(t){if(t!=this.disabled){this.disabled=!!t;if(t){if(this.action){this.pCont.setAttribute("data-bx-type","")}BX.addClass(this.pCont,this.disabledClassName)}else{if(this.action){this.pCont.setAttribute("data-bx-type","action")}BX.removeClass(this.pCont,this.disabledClassName)}}},OnClick:BX.DoNothing,OnMouseUp:function(){if(!this.checked){BX.removeClass(this.pCont,this.activeClassName)}BX.unbind(document,"mouseup",BX.proxy(this.OnMouseUp,this));BX.removeCustomEvent(this.editor,"OnIframeMouseUp",BX.proxy(this.OnMouseUp,this))},OnMouseDown:function(){if(!this.disabled){if(this.disabledForTextarea||!this.editor.synchro.IsFocusedOnTextarea()){this.savedRange=this.editor.selection.SaveBookmark()}BX.addClass(this.pCont,this.activeClassName);BX.bind(document,"mouseup",BX.proxy(this.OnMouseUp,this));BX.addCustomEvent(this.editor,"OnIframeMouseUp",BX.proxy(this.OnMouseUp,this))}},GetValue:function(){return!!this.checked},SetValue:function(t){this.Check(t)}};function h(t){this.editor=t;this.className="bxhtmled-top-bar-btn";this.activeClassName="bxhtmled-top-bar-btn-active";this.activeListClassName="bxhtmled-top-bar-btn-active";this.arValues=[];this.checkableAction=true;this.disabledForTextarea=true;this.posOffset={top:6,left:-4};this.zIndex=3005}h.prototype={Create:function(){this.pCont=BX.create("SPAN",{props:{className:this.className},html:"<i></i>"});this.pValuesCont=BX.create("DIV",{props:{className:"bxhtmled-popup bxhtmled-dropdown-cont"},html:'<div class="bxhtmled-popup-corner"></div>'});if(this.title){this.pCont.title=this.title}if(this.zIndex){this.pValuesCont.style.zIndex=this.zIndex}this.valueIndex={};this.pValuesContWrap=this.pValuesCont.appendChild(BX.create("DIV"));var t,e,i=this;for(var s=0;s<this.arValues.length;s++){e=this.arValues[s];t=this.pValuesContWrap.appendChild(BX.create("SPAN",{props:{title:e.title,className:e.className},html:"<i></i>"}));t.setAttribute("data-bx-dropdown-value",e.id);this.valueIndex[e.id]=s;if(e.action){t.setAttribute("data-bx-type","action");t.setAttribute("data-bx-action",e.action);if(e.value){t.setAttribute("data-bx-value",e.value)}}BX.bind(t,"mousedown",function(t){i.SelectItem(this.getAttribute("data-bx-dropdown-value"));i.editor.CheckCommand(this);i.Close()});this.arValues[s].listCont=t}if(this.action&&this.checkableAction){this.editor.RegisterCheckableAction(this.action,{action:this.action,control:this})}BX.bind(this.pCont,"click",BX.proxy(this.OnClick,this));BX.bind(this.pCont,"mousedown",BX.delegate(this.OnMouseDown,this))},GetCont:function(){return this.pCont},GetPopupBindCont:function(){return this.pCont},Disable:function(t){if(t!=this.disabled){this.disabled=!!t;if(t){BX.addClass(this.pCont,"bxhtmled-top-bar-btn-disabled")}else{BX.removeClass(this.pCont,"bxhtmled-top-bar-btn-disabled")}}},OnKeyDown:function(t){if(t.keyCode==27){this.Close()}},OnClick:function(){if(!this.disabled){if(this.bOpened){this.Close()}else{this.Open()}}},OnMouseUp:function(){this.editor.selection.RestoreBookmark();if(!this.checked){BX.removeClass(this.pCont,this.activeClassName)}BX.unbind(document,"mouseup",BX.proxy(this.OnMouseUp,this));BX.removeCustomEvent(this.editor,"OnIframeMouseUp",BX.proxy(this.OnMouseUp,this))},OnMouseDown:function(){if(!this.disabled){if(this.disabledForTextarea||!this.editor.synchro.IsFocusedOnTextarea()){this.savedRange=this.editor.selection.SaveBookmark()}BX.addClass(this.pCont,this.activeClassName);BX.bind(document,"mouseup",BX.proxy(this.OnMouseUp,this));BX.addCustomEvent(this.editor,"OnIframeMouseUp",BX.proxy(this.OnMouseUp,this))}},Close:function(){var t=this;this.popupShownTimeout=setTimeout(function(){t.editor.popupShown=false},300);BX.removeClass(this.pCont,this.activeClassName);this.pValuesCont.style.display="none";this.editor.overlay.Hide();BX.unbind(window,"keydown",BX.proxy(this.OnKeyDown,this));BX.unbind(document,"mousedown",BX.proxy(this.CheckClose,this));BX.onCustomEvent(this,"OnPopupClose");this.bOpened=false},CheckClose:function(t){if(!this.bOpened){return BX.unbind(document,"mousedown",BX.proxy(this.CheckClose,this))}var e;if(t.target)e=t.target;else if(t.srcElement)e=t.srcElement;if(e.nodeType==3)e=e.parentNode;if(!BX.findParent(e,{className:"bxhtmled-popup"})){this.Close()}},Open:function(){this.editor.popupShown=true;if(this.popupShownTimeout){this.popupShownTimeout=clearTimeout(this.popupShownTimeout)}document.body.appendChild(this.pValuesCont);this.pValuesCont.style.display="block";BX.addClass(this.pCont,this.activeClassName);var t=this.editor.overlay.Show({zIndex:this.zIndex-1}),e=this.GetPopupBindCont(),i=BX.pos(e),s=Math.round(i.left-this.pValuesCont.offsetWidth/2+e.offsetWidth/2+this.posOffset.left),o=Math.round(i.bottom+this.posOffset.top),n=this;BX.bind(window,"keydown",BX.proxy(this.OnKeyDown,this));t.onclick=function(){n.Close()};this.pValuesCont.style.top=o+"px";this.pValuesCont.style.left=s+"px";this.bOpened=true;setTimeout(function(){BX.bind(document,"mousedown",BX.proxy(n.CheckClose,n))},100)},SelectItem:function(t,e){if(!e)e=this.arValues[this.valueIndex[t]];if(this.lastActiveItem)BX.removeClass(this.lastActiveItem,this.activeListClassName);if(e){if(e.listCont){this.lastActiveItem=e.listCont;BX.addClass(e.listCont,this.activeListClassName)}this.pCont.className=e.className;this.pCont.title=BX.util.htmlspecialchars(e.title||e.name||"")}else{this.pCont.className=this.className;this.pCont.title=this.title}if(this.disabled){this.disabled=false;this.Disable(true)}return e},SetValue:function(){},GetValue:function(){}};function d(t){d.superclass.constructor.apply(this,arguments);this.className="bxhtmled-top-bar-select";this.itemClassName="bxhtmled-dd-list-item";this.activeListClassName="bxhtmled-dd-list-item-active";this.disabledForTextarea=true}BX.extend(d,h);d.prototype.Create=function(){this.pCont=BX.create("SPAN",{props:{className:this.className,title:this.title},attrs:{unselectable:"on"},text:""});if(this.width)this.pCont.style.width=this.width+"px";this.pValuesCont=BX.create("DIV",{props:{className:"bxhtmled-popup bxhtmled-dropdown-list-cont"},html:'<div class="bxhtmled-popup-corner"></div>'});this.pValuesContWrap=this.pValuesCont.appendChild(BX.create("DIV",{props:{className:"bxhtmled-dd-list-wrap"}}));this.valueIndex={};if(this.zIndex){this.pValuesCont.style.zIndex=this.zIndex}var t,e,i=this,s,o,n;for(o=0;o<this.arValues.length;o++){e=this.arValues[o];s=this.itemClassName;if(e.className)s+=" "+e.className;n=e.tagName?"<"+e.tagName+">"+e.name+"</"+e.tagName+">":e.name;t=this.pValuesContWrap.appendChild(BX.create("SPAN",{props:{title:e.title||e.name,className:s},html:n,style:e.style}));t.setAttribute("data-bx-dropdown-value",e.id);this.valueIndex[e.id]=o;if(e.defaultValue)this.SelectItem(null,e);if(e.action){t.setAttribute("data-bx-type","action");t.setAttribute("data-bx-action",e.action);if(e.value)t.setAttribute("data-bx-value",e.value)}BX.bind(t,"mousedown",function(t){if(!t)t=window.event;i.SelectItem(this.getAttribute("data-bx-dropdown-value"));i.editor.CheckCommand(this)});this.arValues[o].listCont=t}if(this.action&&this.checkableAction){this.editor.RegisterCheckableAction(this.action,{action:this.action,control:this})}BX.bind(this.pCont,"click",BX.proxy(this.OnClick,this))};d.prototype.SelectItem=function(t,e,i){i=i!==false;if(!e){e=this.arValues[this.valueIndex[t]]}if(this.lastActiveItem){BX.removeClass(this.lastActiveItem,this.activeListClassName)}if(e){this.pCont.innerHTML=BX.util.htmlspecialchars(e.topName||e.name||e.id);this.pCont.title=this.title+": "+BX.util.htmlspecialchars(e.title||e.name);if(e.listCont){this.lastActiveItem=e.listCont;BX.addClass(e.listCont,this.activeListClassName)}}if(this.bOpened&&i){this.Close()}};d.prototype.SetValue=function(t,e){};d.prototype.SetWidth=function(t){t=parseInt(t,10);if(t){this.width=t;this.pCont.style.width=t+"px"}};d.prototype.Disable=function(t){if(t!=this.disabled){this.disabled=!!t;if(t){BX.addClass(this.pCont,"bxhtmled-top-bar-select-disabled")}else{BX.removeClass(this.pCont,"bxhtmled-top-bar-select-disabled")}}};function c(t,e){this.values=[];this.pInput=e.input;this.editor=t;this.value=e.value||"";this.defaultValue=e.defaultValue||"";this.posOffset={top:8,left:-4};this.zIndex=3010;this.SPLIT_SYMBOL=",";this.itemClassName="bxhtmled-dd-list-item";this.itemClassNameActive="bxhtmled-dd-list-item-active"}c.prototype={Init:function(){BX.bind(this.pInput,"focus",BX.proxy(this.Focus,this));BX.bind(this.pInput,"click",BX.proxy(this.Focus,this));BX.bind(this.pInput,"blur",BX.proxy(this.Blur,this));BX.bind(this.pInput,"keyup",BX.proxy(this.KeyUp,this));this.visibleItemsLength=this.values.length;this.currentItem=false},UpdateValues:function(t){this.bCreated=false;this.values=t;this.visibleItemsLength=this.values.length;this.currentItem=false;if(this.bOpened){this.ClosePopup()}},Create:function(){this.pValuesCont=BX.create("DIV",{props:{className:"bxhtmled-popup bxhtmled-combo-cont"},html:'<div class="bxhtmled-popup-corner"></div>'});this.pValuesCont.style.zIndex=this.zIndex;if(this.pValuesContWrap){BX.cleanNode(this.pValuesContWrap);this.pValuesCont.appendChild(this.pValuesContWrap)}else{this.pValuesContWrap=this.pValuesCont.appendChild(BX.create("DIV",{props:{className:"bxhtmled-dd-list-wrap"}}));BX.bind(this.pValuesContWrap,"mousedown",function(t){var e=t.target||t.srcElement;if(!e.getAttribute("data-bx-dropdown-value")){e=BX.findParent(e,function(t){return t.getAttribute&&t.getAttribute("data-bx-dropdown-value")},i.pValuesContWrap)}if(e){i.currentItem=parseInt(e.getAttribute("data-bx-dropdown-value"),10);i.SetValueFromList()}i.ClosePopup()})}this.valueIndex={};var t,e,i=this,s,o,n;for(o=0;o<this.values.length;o++){e=this.values[o];s=this.itemClassName||"";this.values[o].TITLE=this.values[o].TITLE||this.values[o].NAME;if(this.values[o].VALUE){this.values[o].TITLE+=" ("+this.values[o].VALUE+")"}else{this.values[o].VALUE=this.values[o].NAME}t=this.pValuesContWrap.appendChild(BX.create("SPAN",{props:{className:s},html:e.TITLE}));t.setAttribute("data-bx-dropdown-value",o);this.values[o].cont=t}this.bCreated=true},KeyUp:function(t){var e=t.keyCode;if(e==this.editor.KEY_CODES["down"]){this.SelectItem(1)}else if(e==this.editor.KEY_CODES["up"]){this.SelectItem(-1)}else if(e==this.editor.KEY_CODES["escape"]){if(this.bOpened){this.ClosePopup();return BX.PreventDefault(t)}}else if(e==this.editor.KEY_CODES["enter"]){if(this.bOpened){this.SetValueFromList();this.ClosePopup();return BX.PreventDefault(t)}}else{this.FilterValue()}},FilterValue:function(){var t,e,i=this.GetSplitedValues(),s=this.GetCaretPos(this.pInput);for(t=0;t<i.length;t++){e=i[t];if(s>=e.start&&s<=e.end){break}}this.FilterAndHighlight(e.value)},GetSplitedValues:function(){var t,e,i,s,o,n=[],a=this.pInput.value;if(a.indexOf(this.SPLIT_SYMBOL)===-1||this.bMultiple===false){n.push({start:0,end:a.length,value:BX.util.trim(a)})}else{t=a.split(this.SPLIT_SYMBOL);i=0;s=0;for(e=0;e<t.length;e++){o=t[e];s+=o.length+e;n.push({start:i,end:s,value:BX.util.trim(o)});i=s}}return n},FilterAndHighlight:function(t){t=BX.util.trim(t);var e,i,s=false,o;this.visibleItemsLength=0;for(i=0;i<this.values.length;i++){e=this.values[i];if(t===""){s=true;e.cont.style.display="";this.visibleItemsLength++}else{o=e.TITLE.toLowerCase().indexOf(t.toLowerCase());if(o!==-1||t==""){e.cont.innerHTML=BX.util.htmlspecialchars(e.TITLE.substr(0,o))+"<b>"+BX.util.htmlspecialchars(t)+"</b>"+BX.util.htmlspecialchars(e.TITLE.substr(o+t.length));s=true;e.cont.style.display="";e.cont.setAttribute("data-bx-dropdown-value",this.visibleItemsLength);this.visibleItemsLength++}else{e.cont.innerHTML=BX.util.htmlspecialchars(e.TITLE);e.cont.style.display="none"}}}this.currentItem=false;if(s&&!this.bOpened){this.ShowPopup()}else if(!s&&this.bOpened){this.ClosePopup()}},GetCaretPos:function(t){var e=0;if(document.selection){BX.focus(t);var i=document.selection.createRange();i.moveStart("character",-t.value.length);e=i.text.length}else if(t.selectionStart||t.selectionStart=="0"){e=t.selectionStart}return e},SetValue:function(t){this.pInput.value=t},SetValueFromList:function(){var t=0,e,i;for(i=0;i<this.values.length;i++){e=this.values[i];if(e.cont.style.display!="none"){if(t==this.currentItem){BX.addClass(e.cont,this.itemClassNameActive);break}t++}}var s,o=this.GetSplitedValues(),n=this.GetCaretPos(this.pInput);for(i=0;i<o.length;i++){s=o[i];if(n>=s.start&&n<=s.end){break}}var a=this.SPLIT_SYMBOL==" "?" ":this.SPLIT_SYMBOL+" ",r=this.pInput.value,l=r.substr(0,s.start),h=r.substr(s.end);l=l.replace(/^[\s\r\n\,]+/g,"").replace(/[\s\r\n\,]+$/g,"");h=h.replace(/^[\s\r\n\,]+/g,"").replace(/[\s\r\n\,]+$/g,"");this.pInput.value=l+(l==""?"":a)+e.VALUE+(h==""?"":a)+h;this.FilterAndHighlight("")},SelectItem:function(t){var e,i,s,o;if(this.currentItem===false){this.currentItem=0}else if(t!==undefined){this.currentItem+=t;if(this.currentItem>this.visibleItemsLength-1){this.currentItem=0}else if(this.currentItem<0){this.currentItem=this.visibleItemsLength-1}}if(document.querySelectorAll){var n=this.pValuesContWrap.querySelectorAll("."+this.itemClassNameActive);if(n){for(s=0;s<n.length;s++){BX.removeClass(n[s],this.itemClassNameActive)}}}e=0;o=this.values.length;for(s=0;s<this.values.length;s++){i=this.values[s];if(i.cont.style.display!="none"){if(e==this.currentItem){BX.addClass(i.cont,this.itemClassNameActive);break}e++}}},Focus:function(t){if(this.values.length>0&&!this.bFocused){BX.focus(this.pInput);this.bFocused=true;if(this.value==this.defaultValue){this.value=""}this.ShowPopup()}},Blur:function(){if(this.values.length>0&&this.bFocused){this.bFocused=false;this.ClosePopup()}},ShowPopup:function(){if(!this.bCreated){this.Create()}this.editor.popupShown=true;if(this.popupShownTimeout){this.popupShownTimeout=clearTimeout(this.popupShownTimeout)}document.body.appendChild(this.pValuesCont);this.pValuesCont.style.display="block";var t,e=BX.pos(this.pInput),i=e.left+this.posOffset.left,s=e.bottom+this.posOffset.top;this.pValuesCont.style.top=s+"px";this.pValuesCont.style.left=i+"px";this.bOpened=true;if(document.querySelectorAll){var o=this.pValuesContWrap.querySelectorAll("."+this.itemClassNameActive);if(o){for(t=0;t<o.length;t++){BX.removeClass(o[t],this.itemClassNameActive)}}}BX.onCustomEvent(this,"OnComboPopupOpen")},ClosePopup:function(){var t=this;this.popupShownTimeout=setTimeout(function(){t.editor.popupShown=false},300);this.pValuesCont.style.display="none";this.editor.overlay.Hide();this.bOpened=false;BX.onCustomEvent(this,"OnComboPopupClose")},OnChange:function(){},CheckClose:function(t){if(!this.bOpened){return BX.unbind(document,"mousedown",BX.proxy(this.CheckClose,this))}var e;if(t.target)e=t.target;else if(t.srcElement)e=t.srcElement;if(e.nodeType==3)e=e.parentNode;if(!BX.findParent(e,{className:"bxhtmled-popup"}))this.Close()}};function u(t,e){u.superclass.constructor.apply(this,arguments);this.filterTag=e.filterTag||"";this.lastTemplateId=this.editor.GetTemplateId();this.values=this.GetClasses();this.SPLIT_SYMBOL=" ";this.Init()}BX.extend(u,c);u.prototype.OnChange=function(){if(this.lastTemplateId!=this.editor.GetTemplateId()){this.lastTemplateId=this.editor.GetTemplateId();this.values=this.GetClasses();this.bCreated=false}};u.prototype.GetClasses=function(){var t=this.editor.GetCurrentCssClasses(this.filterTag);this.values=[];if(t&&t.length>0){for(var e=0;e<t.length;e++){this.values.push({VALUE:t[e].className,TITLE:t[e].classTitle,NAME:t[e].className})}}return this.values};function p(){window.BXHtmlEditor.TaskbarManager=t;window.BXHtmlEditor.Taskbar=e;window.BXHtmlEditor.ComponentsControl=i;window.BXHtmlEditor.ContextMenu=o;window.BXHtmlEditor.Dialog=s;window.BXHtmlEditor.Toolbar=n;window.BXHtmlEditor.NodeNavigator=a;window.BXHtmlEditor.Button=l;window.BXHtmlEditor.DropDown=h;window.BXHtmlEditor.DropDownList=d;window.BXHtmlEditor.ComboBox=c;window.BXHtmlEditor.ClassSelector=u;window.BXHtmlEditor.Overlay=r;BX.onCustomEvent(window.BXHtmlEditor,"OnEditorBaseControlsDefined")}if(window.BXHtmlEditor){p()}else{BX.addCustomEvent(window,"OnBXHtmlEditorInit",p)}})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:68:"/bitrix/js/fileman/html_editor/html-controls.min.js?1452277448115821";s:6:"source";s:47:"/bitrix/js/fileman/html_editor/html-controls.js";s:3:"min";s:51:"/bitrix/js/fileman/html_editor/html-controls.min.js";s:3:"map";s:51:"/bitrix/js/fileman/html_editor/html-controls.map.js";}"*/
(function(){function t(){var t=window.BXHtmlEditor.Button,e=window.BXHtmlEditor.Dialog;function i(t,e,i){this.editor=t;this.params=i||{};this.className="bxhtmled-top-bar-btn bxhtmled-top-bar-color";this.activeClassName="bxhtmled-top-bar-btn-active";this.disabledClassName="bxhtmled-top-bar-btn-disabled";this.bCreated=false;this.zIndex=3009;this.disabledForTextarea=true;this.posOffset={top:6,left:0};this.id="color";this.title=BX.message("BXEdForeColor");this.actionColor="foreColor";this.actionBg="backgroundColor";this.showBgMode=!this.editor.bbCode;this.disabledForTextarea=!t.bbCode;this.Create();if(e){e.appendChild(this.GetCont())}}i.prototype={Create:function(){this.pCont=BX.create("SPAN",{props:{className:this.className,title:this.title||""}});this.pContLetter=this.pCont.appendChild(BX.create("SPAN",{props:{className:"bxhtmled-top-bar-btn-text"},html:"A"}));this.pContStrip=this.pCont.appendChild(BX.create("SPAN",{props:{className:"bxhtmled-top-bar-color-strip"}}));this.currentAction=this.actionColor;BX.bind(this.pCont,"click",BX.delegate(this.OnClick,this));BX.bind(this.pCont,"mousedown",BX.delegate(this.OnMouseDown,this));if(this.params.registerActions!==false){this.editor.RegisterCheckableAction(this.actionColor,{action:this.actionColor,control:this,value:this.value});this.editor.RegisterCheckableAction(this.actionBg,{action:this.actionBg,control:this,value:this.value})}},GetCont:function(){return this.pCont},Check:function(t){if(t!=this.checked&&!this.disabled){this.checked=t;if(this.checked){BX.addClass(this.pCont,"bxhtmled-top-bar-btn-active")}else{BX.removeClass(this.pCont,"bxhtmled-top-bar-btn-active")}}},Disable:function(t){if(t!=this.disabled){this.disabled=!!t;if(t){BX.addClass(this.pCont,"bxhtmled-top-bar-btn-disabled")}else{BX.removeClass(this.pCont,"bxhtmled-top-bar-btn-disabled")}}},GetValue:function(){return!!this.checked},SetValue:function(t,e,i){if(e&&e[0]){var s=i==this.actionColor?e[0].style.color:e[0].style.backgroundColor;this.SelectColor(s,i)}else{this.SelectColor(null,i)}},OnClick:function(){if(this.disabled){return false}if(this.bOpened){return this.Close()}this.Open()},OnMouseUp:function(){this.editor.selection.RestoreBookmark();if(!this.checked){BX.removeClass(this.pCont,this.activeClassName)}BX.unbind(document,"mouseup",BX.proxy(this.OnMouseUp,this));BX.removeCustomEvent(this.editor,"OnIframeMouseUp",BX.proxy(this.OnMouseUp,this))},OnMouseDown:function(){if(!this.disabled){if(this.disabledForTextarea||!this.editor.synchro.IsFocusedOnTextarea()){this.editor.selection.SaveBookmark()}BX.addClass(this.pCont,this.activeClassName);BX.bind(document,"mouseup",BX.proxy(this.OnMouseUp,this));BX.addCustomEvent(this.editor,"OnIframeMouseUp",BX.proxy(this.OnMouseUp,this))}},Close:function(){var t=this;this.popupShownTimeout=setTimeout(function(){t.editor.popupShown=false},300);this.pValuesCont.style.display="none";BX.removeClass(this.pCont,this.activeClassName);this.editor.overlay.Hide();BX.unbind(window,"keydown",BX.proxy(this.OnKeyDown,this));BX.unbind(document,"mousedown",BX.proxy(this.CheckClose,this));this.bOpened=false},CheckClose:function(t){if(!this.bOpened){return BX.unbind(document,"mousedown",BX.proxy(this.CheckClose,this))}var e;if(t.target)e=t.target;else if(t.srcElement)e=t.srcElement;if(e.nodeType==3)e=e.parentNode;if(e!==this.custInp&&!BX.findParent(e,{className:"lhe-colpick-cont"})){this.Close()}},Open:function(){this.editor.popupShown=true;if(this.popupShownTimeout){this.popupShownTimeout=clearTimeout(this.popupShownTimeout)}var t=this;if(!this.bCreated){this.pValuesCont=document.body.appendChild(BX.create("DIV",{props:{className:"bxhtmled-popup  bxhtmled-color-cont"},style:{zIndex:this.zIndex},html:'<div class="bxhtmled-popup-corner"></div>'}));if(this.showBgMode){this.pTextColorLink=this.pValuesCont.appendChild(BX.create("SPAN",{props:{className:"bxhtmled-color-link bxhtmled-color-link-active"},text:this.params.ForeColorMess||BX.message("BXEdForeColor")}));this.pTextColorLink.setAttribute("data-bx-type","changeColorAction");this.pTextColorLink.setAttribute("data-bx-value",this.actionColor);this.pBgColorLink=this.pValuesCont.appendChild(BX.create("SPAN",{props:{className:"bxhtmled-color-link"},text:this.params.BgColorMess||BX.message("BXEdBackColor")}));this.pBgColorLink.setAttribute("data-bx-type","changeColorAction");this.pBgColorLink.setAttribute("data-bx-value",this.actionBg)}this.pValuesContWrap=this.pValuesCont.appendChild(BX.create("DIV",{props:{className:"bxhtmled-color-wrap"}}));BX.bind(this.pValuesCont,"mousedown",function(e){var i=e.target||e.srcElement,s;if(i!=t.pValuesCont){s=i&&i.getAttribute?i.getAttribute("data-bx-type"):null;if(!s){i=BX.findParent(i,function(e){return e==t.pValuesCont||e.getAttribute&&e.getAttribute("data-bx-type")},t.pValuesCont);s=i&&i.getAttribute?i.getAttribute("data-bx-type"):null}if(s=="customColorAction"){var a=i.getAttribute("data-bx-value");if(a=="link"){t.ShowCustomColor(true,t.colorCell.style.backgroundColor);BX.PreventDefault(e)}if(a=="button"){t.SelectColor(t.custInp.value);if(t.params.checkAction!==false&&t.editor.action.IsSupported(t.currentAction)){t.editor.action.Exec(t.currentAction,t.custInp.value)}}}else if(s=="changeColorAction"){if(t.showBgMode){t.SetMode(i.getAttribute("data-bx-value"));BX.PreventDefault(e)}}else if(i&&s){i.setAttribute("data-bx-action",t.currentAction);if(t.params.checkAction!==false){t.editor.CheckCommand(i)}t.SelectColor(i.getAttribute("data-bx-value"))}}});var e=["#FF0000","#FFFF00","#00FF00","#00FFFF","#0000FF","#FF00FF","#FFFFFF","#EBEBEB","#E1E1E1","#D7D7D7","#CCCCCC","#C2C2C2","#B7B7B7","#ACACAC","#A0A0A0","#959595","#EE1D24","#FFF100","#00A650","#00AEEF","#2F3192","#ED008C","#898989","#7D7D7D","#707070","#626262","#555555","#464646","#363636","#262626","#111111","#000000","#F7977A","#FBAD82","#FDC68C","#FFF799","#C6DF9C","#A4D49D","#81CA9D","#7BCDC9","#6CCFF7","#7CA6D8","#8293CA","#8881BE","#A286BD","#BC8CBF","#F49BC1","#F5999D","#F16C4D","#F68E54","#FBAF5A","#FFF467","#ACD372","#7DC473","#39B778","#16BCB4","#00BFF3","#438CCB","#5573B7","#5E5CA7","#855FA8","#A763A9","#EF6EA8","#F16D7E","#EE1D24","#F16522","#F7941D","#FFF100","#8FC63D","#37B44A","#00A650","#00A99E","#00AEEF","#0072BC","#0054A5","#2F3192","#652C91","#91278F","#ED008C","#EE105A","#9D0A0F","#A1410D","#A36209","#ABA000","#588528","#197B30","#007236","#00736A","#0076A4","#004A80","#003370","#1D1363","#450E61","#62055F","#9E005C","#9D0039","#790000","#7B3000","#7C4900","#827A00","#3E6617","#045F20","#005824","#005951","#005B7E","#003562","#002056","#0C004B","#30004A","#4B0048","#7A0045","#7A0026"];var i,s,a,l=BX.create("TABLE",{props:{className:"bxhtmled-color-tbl"}}),o,n=e.length;this.pDefValueRow=l.insertRow(-1);s=this.pDefValueRow.insertCell(-1);s.colSpan=5;var r=s.appendChild(BX.create("SPAN",{props:{className:"bxhtmled-color-def-but"}}));r.innerHTML=BX.message("BXEdDefaultColor");r.setAttribute("data-bx-type","action");r.setAttribute("data-bx-action",this.action);r.setAttribute("data-bx-value","");a=this.pDefValueRow.insertCell(-1);a.colSpan=5;a.className="bxhtmled-color-inp-cell";a.style.backgroundColor=e[38];this.colorCell=a;s=this.pDefValueRow.insertCell(-1);s.colSpan=6;this.custLink=s.appendChild(BX.create("SPAN",{props:{className:"bxhtmled-color-custom"},html:BX.message("BXEdColorOther")}));this.custLink.setAttribute("data-bx-type","customColorAction");this.custLink.setAttribute("data-bx-value","link");this.custInp=s.appendChild(BX.create("INPUT",{props:{type:"text",className:"bxhtmled-color-custom-inp"},style:{display:"none"}}));this.custInp.setAttribute("data-bx-type","customColorAction");this.custInp.setAttribute("data-bx-value","input");this.custBut=s.appendChild(BX.create("INPUT",{props:{type:"button",className:"bxhtmled-color-custom-but",value:"ok"},style:{display:"none"}}));this.custBut.setAttribute("data-bx-type","customColorAction");this.custBut.setAttribute("data-bx-value","button");for(o=0;o<n;o++){if(Math.round(o/16)==o/16){i=l.insertRow(-1)}s=i.insertCell(-1);s.innerHTML="&nbsp;";s.className="bxhtmled-color-col-cell";s.style.backgroundColor=e[o];s.id="bx_color_id__"+o;s.setAttribute("data-bx-type","action");s.setAttribute("data-bx-action",this.action);s.setAttribute("data-bx-value",e[o]);s.onmouseover=function(t){this.className="bxhtmled-color-col-cell bxhtmled-color-col-cell-over";a.style.backgroundColor=e[this.id.substring("bx_color_id__".length)]};s.onmouseout=function(t){this.className="bxhtmled-color-col-cell"};s.onclick=function(i){t.Select(e[this.id.substring("bx_color_id__".length)])}}this.pValuesContWrap.appendChild(l);this.bCreated=true}document.body.appendChild(this.pValuesCont);this.pDefValueRow.style.display=t.editor.synchro.IsFocusedOnTextarea()?"none":"";this.pValuesCont.style.display="block";var h=this.editor.overlay.Show(),d=BX.pos(this.pCont),p=d.left-this.pValuesCont.offsetWidth/2+this.pCont.offsetWidth/2+this.posOffset.left,c=d.bottom+this.posOffset.top;BX.bind(window,"keydown",BX.proxy(this.OnKeyDown,this));BX.addClass(this.pCont,this.activeClassName);h.onclick=function(){t.Close()};this.pValuesCont.style.left=p+"px";this.pValuesCont.style.top=c+"px";this.bOpened=true;setTimeout(function(){BX.bind(document,"mousedown",BX.proxy(t.CheckClose,t))},100);this.ShowCustomColor(false,"")},SetMode:function(t){this.currentAction=t;var e="bxhtmled-color-link-active";if(t==this.actionColor){BX.addClass(this.pTextColorLink,e);BX.removeClass(this.pBgColorLink,e)}else{BX.addClass(this.pBgColorLink,e);BX.removeClass(this.pTextColorLink,e)}},SelectColor:function(t,e){if(!e){e=this.currentAction}if(this.params.callback&&typeof this.params.callback=="function"){this.params.callback(e,this.editor.util.RgbToHex(t))}if(e==this.actionColor){this.pContLetter.style.color=t||"#000";this.pContStrip.style.backgroundColor=t||"#000"}else{this.pContLetter.style.backgroundColor=t||"transparent"}},ShowCustomColor:function(t,e){if(t!==false){this.custInp.style.display="";this.custBut.style.display="";this.custLink.style.display="none"}else{this.custInp.style.display="none";this.custBut.style.display="none";this.custLink.style.display=""}if(e)e=this.editor.util.RgbToHex(e);this.custInp.value=e.toUpperCase()||""}};function s(t,e){s.superclass.constructor.apply(this,arguments);this.id="search";this.title=BX.message("ButtonSearch");this.className+=" bxhtmled-button-search";this.Create();this.bInited=false;if(e)e.appendChild(this.GetCont())}BX.extend(s,t);s.prototype.OnClick=function(){if(this.disabled)return;if(!this.bInited){var t=this;this.pSearchCont=BX("bx-html-editor-search-cnt-"+this.editor.id);this.pSearchWrap=this.pSearchCont.appendChild(BX.create("DIV",{props:{className:"bxhtmled-search-cnt-search"}}));this.pReplaceWrap=this.pSearchCont.appendChild(BX.create("DIV",{props:{className:"bxhtmled-search-cnt-replace"}}));this.pSearchInput=this.pSearchWrap.appendChild(BX.create("INPUT",{props:{className:"bxhtmled-top-search-inp",type:"text"}}));this.pShowReplace=this.pSearchWrap.appendChild(BX.create("INPUT",{props:{type:"checkbox",value:"Y"}}));this.pReplaceInput=this.pReplaceWrap.appendChild(BX.create("INPUT",{props:{type:"text"}}));BX.bind(this.pShowReplace,"click",function(){t.ShowReplace(!!this.checked)});this.animation=null;this.animationStartHeight=0;this.animationEndHeight=0;this.height0=0;this.height1=37;this.height2=66;this.bInited=true;this.bReplaceOpened=false}if(!this.bOpened)this.OpenPanel();else this.ClosePanel()};s.prototype.SetPanelHeight=function(t,e){this.pSearchCont.style.height=t+"px";this.pSearchCont.style.opacity=e/100;this.editor.SetAreaContSize(this.origAreaWidth,this.origAreaHeight-t,{areaContTop:this.editor.toolbar.GetHeight()+t})};s.prototype.OpenPanel=function(t){this.pSearchCont.style.display="block";if(this.animation)this.animation.stop();if(t){this.animationStartHeight=this.height1;this.animationEndHeight=this.height2}else{this.origAreaHeight=parseInt(this.editor.dom.areaCont.style.height,10);this.origAreaWidth=parseInt(this.editor.dom.areaCont.style.width,10);this.pShowReplace.checked=false;this.pSearchCont.style.opacity=0;this.animationStartHeight=this.height0;this.animationEndHeight=this.height1}var e=this;this.animation=new BX.easing({duration:300,start:{height:this.animationStartHeight,opacity:t?100:0},finish:{height:this.animationEndHeight,opacity:100},transition:BX.easing.makeEaseOut(BX.easing.transitions.quart),step:function(t){e.SetPanelHeight(t.height,t.opacity)},complete:BX.proxy(function(){this.animation=null},this)});this.animation.animate();this.bOpened=true};s.prototype.ClosePanel=function(t){if(this.animation)this.animation.stop();this.pSearchCont.style.opacity=1;if(t){this.animationStartHeight=this.height2;this.animationEndHeight=this.height1}else{this.animationStartHeight=this.bReplaceOpened?this.height2:this.height1;this.animationEndHeight=this.height0}var e=this;this.animation=new BX.easing({duration:200,start:{height:this.animationStartHeight,opacity:t?100:0},finish:{height:this.animationEndHeight,opacity:100},transition:BX.easing.makeEaseOut(BX.easing.transitions.quart),step:function(t){e.SetPanelHeight(t.height,t.opacity)},complete:BX.proxy(function(){if(!t)this.pSearchCont.style.display="none";this.animation=null},this)});this.animation.animate();if(!t)this.bOpened=false};s.prototype.ShowReplace=function(t){if(t){this.OpenPanel(true);this.bReplaceOpened=true}else{this.ClosePanel(true);this.bReplaceOpened=false}};function a(t,e){a.superclass.constructor.apply(this,arguments);this.id="change_view";this.title=BX.message("ButtonViewMode");this._className=this.className;this.activeClassName="bxhtmled-top-bar-btn-active bxhtmled-top-bar-dd-active";this.topClassName="bxhtmled-top-bar-dd";this.arValues=[{id:"view_wysiwyg",title:BX.message("ViewWysiwyg"),className:this.className+" bxhtmled-button-viewmode-wysiwyg",action:"changeView",value:"wysiwyg"},{id:"view_code",title:BX.message("ViewCode"),className:this.className+" bxhtmled-button-viewmode-code",action:"changeView",value:"code"}];if(!t.bbCode){this.arValues.push({id:"view_split_hor",title:BX.message("ViewSplitHor"),className:this.className+" bxhtmled-button-viewmode-split-hor",action:"splitMode",value:"0"});this.arValues.push({id:"view_split_ver",title:BX.message("ViewSplitVer"),className:this.className+" bxhtmled-button-viewmode-split-ver",action:"splitMode",value:"1"})}this.className+=" bxhtmled-top-bar-dd";this.disabledForTextarea=false;this.Create();if(e)e.appendChild(this.GetCont());var i=this;BX.addCustomEvent(this.editor,"OnSetViewAfter",function(){var t="view_"+i.editor.currentViewName;if(i.editor.currentViewName=="split"){t+="_"+(i.editor.GetSplitMode()?"ver":"hor")}if(t!==i.currentValueId){i.SelectItem(t)}})}BX.extend(a,window.BXHtmlEditor.DropDown);a.prototype.Open=function(){var t=this.editor.IsExpanded();if(!t){var e=BX.pos(this.editor.dom.cont);if(e.left<45)t=true}this.posOffset.left=t?40:-4;a.superclass.Open.apply(this,arguments);this.pValuesCont.firstChild.style.left=t?"20px":""};a.prototype.SelectItem=function(t,e){e=a.superclass.SelectItem.apply(this,[t,e]);if(e){this.pCont.className=this.topClassName+" "+e.className}else{this.pCont.className=this.topClassName+" "+this.className}this.currentValueId=t};function l(t,e){l.superclass.constructor.apply(this,arguments);this.id="bbcode";this.title=BX.message("BXEdBbCode");this.className+=" bxhtmled-button-bbcode";this.disabledForTextarea=false;this.Create();var i=this;BX.addCustomEvent(this.editor,"OnSetViewAfter",function(){i.Check(i.editor.GetViewMode()=="code")});if(e)e.appendChild(this.GetCont())}BX.extend(l,t);l.prototype.OnClick=function(){if(this.disabled)return;if(this.editor.GetViewMode()=="wysiwyg"){this.editor.SetView("code",true);this.Check(true)}else{this.editor.SetView("wysiwyg",true);this.Check(false)}};function o(t,e){o.superclass.constructor.apply(this,arguments);this.id="undo";this.title=BX.message("Undo");this.className+=" bxhtmled-button-undo";this.action="doUndo";this.Create();if(e)e.appendChild(this.GetCont());var i=this;this.Disable(true);this._disabled=true;BX.addCustomEvent(this.editor,"OnEnableUndo",function(t){i._disabled=!t;i.Disable(!t)})}BX.extend(o,t);o.prototype.Disable=function(t){t=t||this._disabled;if(t!=this.disabled){this.disabled=!!t;if(t)BX.addClass(this.pCont,"bxhtmled-top-bar-btn-disabled");else BX.removeClass(this.pCont,"bxhtmled-top-bar-btn-disabled")}};function n(t,e){n.superclass.constructor.apply(this,arguments);this.id="redo";this.title=BX.message("Redo");this.className+=" bxhtmled-button-redo";this.action="doRedo";this.Create();if(e)e.appendChild(this.GetCont());var i=this;this.Disable(true);this._disabled=true;BX.addCustomEvent(this.editor,"OnEnableRedo",function(t){i._disabled=!t;i.Disable(!t)})}BX.extend(n,t);n.prototype.Disable=function(t){t=t||this._disabled;if(t!=this.disabled){this.disabled=!!t;if(t)BX.addClass(this.pCont,"bxhtmled-top-bar-btn-disabled");else BX.removeClass(this.pCont,"bxhtmled-top-bar-btn-disabled")}};function r(t,e){r.superclass.constructor.apply(this,arguments);this.id="style_selector";this.title=BX.message("StyleSelectorTitle");this.className+=" ";this.action="formatStyle";this.itemClassNameGroup="bxhtmled-dd-list-item-gr";this.OPEN_DELAY=800;this.checkedClasses=[];this.checkedTags=this.editor.GetBlockTags();this.arValues=this.GetStyleListValues();this.Create();if(e)e.appendChild(this.GetCont());BX.addCustomEvent(this.editor,"OnApplySiteTemplate",BX.proxy(this.OnTemplateChanged,this))}BX.extend(r,window.BXHtmlEditor.DropDownList);r.prototype.OnTemplateChanged=function(){if(this.bOpened)this.Close();this.arValues=this.GetStyleListValues();this.Create()};r.prototype.GetStyleListValues=function(){this.arValues=[{id:"",name:BX.message("StyleNormal"),topName:BX.message("StyleSelectorName"),tagName:false,action:"formatStyle",value:"",defaultValue:true},{name:BX.message("StyleH2"),className:"bxhtmled-style-h2",tagName:"H2",action:"formatStyle",value:"H2"},{name:BX.message("StyleH3"),className:"bxhtmled-style-h3",tagName:"H3",action:"formatStyle",value:"H3"},{id:"headingsMore",name:BX.message("HeadingMore"),className:"bxhtmled-style-heading-more",items:[{name:BX.message("StyleH1"),className:"bxhtmled-style-h1",tagName:"H1",action:"formatStyle",value:"H1"},{name:BX.message("StyleH4"),className:"bxhtmled-style-h4",tagName:"H4",action:"formatStyle",value:"H4"},{name:BX.message("StyleH5"),className:"bxhtmled-style-h5",tagName:"H5",action:"formatStyle",value:"H5"},{name:BX.message("StyleH6"),className:"bxhtmled-style-h6",tagName:"H6",action:"formatStyle",value:"H6"}]}];var t=this.editor.GetStylesDescription();this.metaClasses=this.GetMetaClassSections();var e,i,s=[],a={},l,o,n;for(e in t){if(t.hasOwnProperty(e)&&typeof t[e]=="object"){i=t[e];if(t[e].section){if(typeof a[i.section]=="undefined"){a[i.section]=s.length;s.push({id:this.metaClasses[i.section].id,name:this.metaClasses[i.section].name,defaultValue:false,items:[]})}s[a[i.section]].items.push({id:e,name:i.title||e,action:"formatStyle",value:{className:e,tag:i.tag||false},html:i.html||false,defaultValue:false})}else{s.push({id:e,name:i.title||e,action:"formatStyle",value:{className:e,tag:i.tag||false},html:i.html||false,defaultValue:false})}this.checkedClasses.push(e);if(i.tag){l=i.tag.indexOf(",")===-1?[i.tag]:i.tag.split(",");for(n=0;n<l.length;n++){o=BX.util.trim(l[n]).toUpperCase();if(!BX.util.in_array(o,this.checkedTags))this.checkedTags.push(o)}}}}if(s.length>0){this.arValues=this.arValues.concat(["separator"],s)}this.arValues.push("separator");this.arValues.push({id:"P",name:BX.message("StyleParagraph"),action:"formatStyle",value:"P"});this.arValues.push({id:"DIV",name:BX.message("StyleDiv"),action:"formatStyle",value:"DIV"});this.editor.On("GetStyleList",[this.styleList]);return this.arValues};r.prototype.GetMetaClassSections=function(){var t={quote:{id:"quote",name:BX.message("BXEdMetaClass_quote")},text:{id:"text",name:BX.message("BXEdMetaClass_text")},block:{id:"block",name:BX.message("BXEdMetaClass_block")},block_icon:{id:"block_icon",name:BX.message("BXEdMetaClass_block_icon")},list:{id:"list",name:BX.message("BXEdMetaClass_list"),activateNodes:["OL","UL"]}};return t};r.prototype.SetValue=function(t,e){this.FilterMetaClasses();var i=false,s,a;if(t){if(e&&e.nodeName){this.FilterMetaClasses(e.nodeName);if(e.className&&e.className!==""){a=e.className;if(e.nodeName=="UL"){var l=this.editor.action.actions.insertUnorderedList.getCustomBullitClass(e);if(l)a=e.className+"~~"+l}for(s in this.valueIndex){if(this.valueIndex.hasOwnProperty(s)&&s.indexOf(a)!==-1){this.SelectItem(s,false,false);i=true;break}}}}if(!i){var o=e.nodeName.toUpperCase();this.SelectItem(o,false,false);i=true}}if(!t||!i){this.SelectItem("",false,false)}};r.prototype.FilterMetaClasses=function(t){for(var e in this.metaClasses){if(this.metaClasses.hasOwnProperty(e)&&this.metaClasses[e].activateNodes&&this.metaClasses[e].itemNode){if(!t){this.metaClasses[e].itemNode.style.display="none"}else if(BX.util.in_array(t.toUpperCase(),this.metaClasses[e].activateNodes)){this.metaClasses[e].itemNode.style.display=""}}}};r.prototype.Create=function(){if(!this.pCont){this.pCont=BX.create("SPAN",{props:{className:this.className,title:this.title},attrs:{unselectable:"on"},text:""});if(this.width)this.pCont.style.width=this.width+"px";BX.bind(this.pCont,"click",BX.proxy(this.OnClick,this))}if(!this.pValuesCont){this.pValuesCont=BX.create("DIV",{props:{className:"bxhtmled-popup bxhtmled-dropdown-list-cont"},html:'<div class="bxhtmled-popup-corner"></div>'});this.pValuesContWrap=this.pValuesCont.appendChild(BX.create("DIV",{props:{className:"bxhtmled-dd-list-wrap"}}))}else{BX.cleanNode(this.pValuesContWrap)}this.valueIndex={};this.itemIndex={};if(this.zIndex){this.pValuesCont.style.zIndex=this.zIndex}var t,e;for(e=0;e<this.arValues.length;e++){t=this.arValues[e];if(t.items&&t.items.length>0){this.CreateSubmenuItem(t,this.pValuesContWrap,e)}else if(!t.items){this.CreateItem(t,this.pValuesContWrap,e)}}if(this.action&&this.checkableAction){this.editor.RegisterCheckableAction(this.action,{action:this.action,control:this})}};r.prototype.CreateItem=function(t,e,i){if(t=="separator"){e.appendChild(BX.create("I",{props:{className:"bxhtmled-dd-list-sep"}}))}else{if(t.tagName){t.tagName=t.tagName.toUpperCase();if(!t.id)t.id=t.tagName}var s=this,a=this.itemClassName+(t.className?" "+t.className:"");if(!t.html){t.html=t.tagName?"<"+t.tagName+">"+t.name+"</"+t.tagName+">":t.name}var l=e.appendChild(BX.create("SPAN",{props:{title:t.title||t.name,className:a},html:t.html,style:t.style}));l.setAttribute("data-bx-dropdown-value",t.id);this.valueIndex[t.id]=i;this.itemIndex[t.id]=t;if(t.defaultValue){this.SelectItem(null,t)}BX.bind(l,"mousedown",function(e){s.SelectItem(this.getAttribute("data-bx-dropdown-value"));if(t.action&&s.editor.action.IsSupported(t.action)){s.editor.action.Exec(t.action,t.value||false)}});this.arValues[i].listCont=l}};r.prototype.CreateSubmenuItem=function(t,e,i){var s=this,a=this.itemClassName+" "+this.itemClassNameGroup+(t.className?" "+t.className:""),l=e.appendChild(BX.create("SPAN",{props:{title:t.title||t.name,className:a},html:t.name+'<i class="bxed-arrow"></i>',style:t.style||""}));l.setAttribute("data-bx-dropdown-value",t.id);this.valueIndex[t.id]=i;this.itemIndex[t.id]=t;this.arValues[i].listCont=l;var o,n=false;var r=this.valueIndex[t.id];var h,d;for(h=0;h<t.items.length;h++){if(t.items[h].tagName){t.items[h].tagName=t.items[h].tagName.toUpperCase();if(!t.items[h].id)t.items[h].id=t.items[h].tagName}d=r+"_"+h;if(!this.arValues[d])this.arValues[d]=t.items[h];this.valueIndex[t.items[h].id]=d;t.items[h].listSubmenuCont=l}BX.bind(l,"mouseover",function(e){n=true;if(o)clearTimeout(o);o=setTimeout(function(){if(n){s.OpenSubmenu(t)}},s.OPEN_DELAY)});BX.bind(l,"mouseout",function(t){n=false;if(o)o=clearTimeout(o)});if(this.metaClasses&&this.metaClasses[t.id]){this.metaClasses[t.id].itemNode=l}};r.prototype.OpenSubmenu=function(t){if(t.id=="list")BX.loadCSS(["/bitrix/css/main/font-awesome.css"]);if(!this.pSubmenuCont){this.pSubmenuCont=BX.create("DIV",{props:{className:"bxhtmled-popup bxhtmled-popup-left bxhtmled-dropdown-list-cont bxhtmled-dropdown-list-cont-submenu"},html:'<div class="bxhtmled-popup-corner"></div>'});this.pSubmenuContWrap=this.pSubmenuCont.appendChild(BX.create("DIV",{props:{className:"bxhtmled-dd-list-wrap"}}))}else{BX.cleanNode(this.pSubmenuContWrap)}if(this.zIndex){this.pSubmenuCont.style.zIndex=this.zIndex}document.body.appendChild(this.pSubmenuCont);this.pSubmenuCont.style.display="block";if(this.curSubmenuItem){BX.removeClass(this.curSubmenuItem,"bxhtmled-dd-list-item-selected")}this.curSubmenuItem=t.listCont;BX.addClass(this.curSubmenuItem,"bxhtmled-dd-list-item-selected");var e=this,i=BX.pos(this.curSubmenuItem),s=i.right+17,a=i.top-9;this.pSubmenuCont.style.top=a+"px";this.pSubmenuCont.style.left=s+"px";var l=this.valueIndex[t.id];var o,n;for(o=0;o<t.items.length;o++){n=l+"_"+o;if(!this.arValues[n])this.arValues[n]=t.items[o];this.CreateItem(t.items[o],this.pSubmenuContWrap,n)}BX.onCustomEvent(this.curSubmenuItem,"OnStyleListSubmenuOpened",[])};r.prototype.CloseSubmenu=function(){if(this.pSubmenuCont&&this.pSubmenuContWrap){BX.cleanNode(this.pSubmenuContWrap);this.pSubmenuCont.style.display="none"}if(this.curSubmenuItem){BX.removeClass(this.curSubmenuItem,"bxhtmled-dd-list-item-selected")}};r.prototype.SelectItem=function(t,e,i){var s=this;i=i!==false;if(!e){e=this.arValues[this.valueIndex[t]];if(!e&&this.valueIndex[t.toUpperCase()]){e=this.arValues[this.valueIndex[t.toUpperCase()]]}if(!e&&this.valueIndex[t.toLowerCase()]){e=this.arValues[this.valueIndex[t.toLowerCase()]]}}if(this.lastActiveSubmenuItem)BX.removeClass(this.lastActiveSubmenuItem,this.activeListClassName);if(this.lastActiveItem)BX.removeClass(this.lastActiveItem,this.activeListClassName);if(e){this.pCont.innerHTML=BX.util.htmlspecialchars(e.topName||e.name||e.id);this.pCont.title=this.title+": "+(e.title||e.name);if(e.listSubmenuCont&&BX.isNodeInDom(e.listSubmenuCont)){this.lastActiveSubmenuItem=e.listSubmenuCont;BX.addClass(e.listSubmenuCont,this.activeListClassName)}if(e.listCont&&BX.isNodeInDom(e.listCont)){this.lastActiveItem=e.listCont;BX.addClass(e.listCont,this.activeListClassName)}else{function a(){if(e.listCont&&BX.isNodeInDom(e.listCont)){if(s.lastActiveItem)BX.removeClass(s.lastActiveItem,s.activeListClassName);s.lastActiveItem=e.listCont;BX.addClass(e.listCont,s.activeListClassName)}BX.removeCustomEvent(e.listSubmenuCont,"OnStyleListSubmenuOpened",a)}if(e.listSubmenuCont){BX.addCustomEvent(e.listSubmenuCont,"OnStyleListSubmenuOpened",a)}}}if(this.bOpened&&i){this.Close()}};r.prototype.Open=function(){r.superclass.Open.apply(this,arguments)};r.prototype.Close=function(){this.CloseSubmenu();r.superclass.Close.apply(this,arguments)};function h(t,e){h.superclass.constructor.apply(this,arguments);this.id="font_selector";this.title=BX.message("FontSelectorTitle");this.action="fontFamily";this.zIndex=3008;var i=this.editor.GetFontFamilyList();this.disabledForTextarea=!t.bbCode;this.arValues=[{id:"",name:BX.message("NoFontTitle"),topName:BX.message("FontSelectorTitle"),title:BX.message("NoFontTitle"),className:"",style:"",action:"fontFamily",value:"",defaultValue:true}];var s,a,l,o;for(s in i){if(i.hasOwnProperty(s)){l=i[s].value;if(typeof l!="object")l=[l];a=i[s].name;o=i[s].arStyle||{fontFamily:l.join(",")};this.arValues.push({id:a,name:a,title:a,className:i[s].className||"",style:i[s].arStyle||{fontFamily:l.join(",")},action:"fontFamily",value:l.join(",")})}}this.Create();if(e){e.appendChild(this.GetCont())}}BX.extend(h,window.BXHtmlEditor.DropDownList);h.prototype.SetValue=function(t,e){if(t){var i,s,a,l,o=this.arValues.length,n=e[0],r=BX.util.trim(BX.style(n,"fontFamily"));if(r!==""&&BX.type.isString(r)){a=r.split(",");for(i in a){l=false;if(a.hasOwnProperty(i)){for(s=0;s<o;s++){a[i]=a[i].replace(/'|"/gi,"");if(this.arValues[s].value.indexOf(a[i])!==-1){l=this.arValues[s].id;break}}if(l!==false){break}}}this.SelectItem(l,false,false)}else{this.SelectItem("",false,false)}}else{this.SelectItem("",false,false)}};function d(t,e){d.superclass.constructor.apply(this,arguments);this.id="font_size";this.title=BX.message("FontSizeTitle");this.className+=" bxhtmled-button-fontsize";this.activeClassName="bxhtmled-top-bar-btn-active bxhtmled-button-fontsize-active";this.disabledClassName="bxhtmled-top-bar-btn-disabled bxhtmled-button-fontsize-disabled";this.action="fontSize";this.zIndex=3007;this.disabledForTextarea=!t.bbCode;var i=[6,7,8,9,10,11,12,13,14,15,16,18,20,22,24,26,28,36,48,72];this.arValues=[{id:"font-size-0",className:"bxhtmled-top-bar-btn bxhtmled-button-remove-fontsize",action:this.action,value:"<i></i>"}];var s,a;for(s in i){if(i.hasOwnProperty(s)){a=i[s];this.arValues.push({id:"font-size-"+a,action:this.action,value:a})}}this.Create();if(e)e.appendChild(this.pCont_);BX.addCustomEvent(this,"OnPopupClose",BX.proxy(this.OnPopupClose,this))}BX.extend(d,window.BXHtmlEditor.DropDown);d.prototype.Create=function(){this.pCont_=BX.create("SPAN",{props:{className:"bxhtmled-button-fontsize-wrap",title:this.title}});this.pCont=this.pButCont=this.pCont_.appendChild(BX.create("SPAN",{props:{className:this.className},html:"<i></i>"}));this.pListCont=this.pCont_.appendChild(BX.create("SPAN",{props:{className:"bxhtmled-top-bar-select",title:this.title},attrs:{unselectable:"on"},text:"",style:{display:"none"}}));this.pValuesCont=BX.create("DIV",{props:{className:"bxhtmled-popup bxhtmled-dropdown-cont"},html:'<div class="bxhtmled-popup-corner"></div>'});this.pValuesCont.style.zIndex=this.zIndex;this.pValuesContWrap=this.pValuesCont.appendChild(BX.create("DIV",{props:{className:"bxhtmled-dropdown-cont bxhtmled-font-size-popup"}}));this.valueIndex={};var t,e,i=this,s,a="bxhtmled-dd-list-item";for(s=0;s<this.arValues.length;s++){e=this.arValues[s];t=this.pValuesContWrap.appendChild(BX.create("SPAN",{props:{className:e.className||a},html:e.value,style:e.style||{}}));t.setAttribute("data-bx-dropdown-value",e.id);this.valueIndex[e.id]=s;if(e.action){t.setAttribute("data-bx-type","action");t.setAttribute("data-bx-action",e.action);if(e.value)t.setAttribute("data-bx-value",e.value)}BX.bind(t,"mousedown",function(t){i.SelectItem(this.getAttribute("data-bx-dropdown-value"));i.editor.CheckCommand(this);i.Close()})}this.editor.RegisterCheckableAction(this.action,{action:this.action,control:this});BX.bind(this.pCont_,"click",BX.proxy(this.OnClick,this));BX.bind(this.pCont,"mousedown",BX.delegate(this.OnMouseDown,this))};d.prototype.SetValue=function(t,e){if(e&&e[0]){var i=e[0];var s=i.style.fontSize;this.SelectItem(false,{value:parseInt(s,10),title:s})}else{this.SelectItem(false,{value:0})}};d.prototype.SelectItem=function(t,e){if(!e)e=this.arValues[this.valueIndex[t]];if(e.value){this.pListCont.innerHTML=e.value;this.pListCont.title=this.title+": "+(e.title||e.value);this.pListCont.style.display="";this.pButCont.style.display="none"}else{this.pListCont.title=this.title;this.pButCont.style.display="";this.pListCont.style.display="none"}};d.prototype.GetPopupBindCont=function(){return this.pCont_};d.prototype.Open=function(){d.superclass.Open.apply(this,arguments);this.pValuesContWrap.firstChild.style.display=this.editor.bbCode&&this.editor.synchro.IsFocusedOnTextarea()?"none":"";BX.addClass(this.pListCont,"bxhtmled-top-bar-btn-active")};d.prototype.Close=function(){d.superclass.Close.apply(this,arguments);BX.removeClass(this.pListCont,"bxhtmled-top-bar-btn-active")};d.prototype.OnPopupClose=function(){var t=this.editor.toolbar.controls.More;setTimeout(function(){if(t&&t.bOpened){t.CheckOverlay()}},100)};function p(t,e){p.superclass.constructor.apply(this,arguments);this.id="bold";this.title=BX.message("Bold");this.className+=" bxhtmled-button-bold";this.action="bold";this.disabledForTextarea=!t.bbCode;this.Create();if(e)e.appendChild(this.GetCont())}BX.extend(p,t);function c(t,e){c.superclass.constructor.apply(this,arguments);this.id="italic";this.title=BX.message("Italic");this.className+=" bxhtmled-button-italic";

this.action="italic";this.disabledForTextarea=!t.bbCode;this.Create();if(e)e.appendChild(this.GetCont())}BX.extend(c,t);function u(t,e){u.superclass.constructor.apply(this,arguments);this.id="underline";this.title=BX.message("Underline");this.className+=" bxhtmled-button-underline";this.action="underline";this.disabledForTextarea=!t.bbCode;this.Create();if(e)e.appendChild(this.GetCont())}BX.extend(u,t);function m(t,e){m.superclass.constructor.apply(this,arguments);this.id="strike";this.title=BX.message("Strike");this.className+=" bxhtmled-button-strike";this.action="strikeout";this.disabledForTextarea=!t.bbCode;this.Create();if(e)e.appendChild(this.GetCont())}BX.extend(m,t);function f(t,e){f.superclass.constructor.apply(this,arguments);this.id="remove_format";this.title=BX.message("RemoveFormat");this.className+=" bxhtmled-button-remove-format";this.action="removeFormat";this.checkableAction=false;this.Create();if(e)e.appendChild(this.GetCont())}BX.extend(f,t);function C(t,e){C.superclass.constructor.apply(this,arguments);this.id="template_selector";this.title=BX.message("TemplateSelectorTitle");this.className+=" ";this.width=85;this.zIndex=3007;this.arValues=[];var i=this.editor.GetTemplateId(),s=this.editor.config.templates,a,l;for(a in s){if(s.hasOwnProperty(a)){l=s[a];this.arValues.push({id:l.value,name:l.name,title:l.name,className:"bxhtmled-button-viewmode-wysiwyg",action:"changeTemplate",value:l.value,defaultValue:l.value==i})}}this.Create();if(e)e.appendChild(this.GetCont());this.SelectItem(i)}BX.extend(C,window.BXHtmlEditor.DropDownList);function b(t,e){b.superclass.constructor.apply(this,arguments);this.id="ordered-list";this.title=BX.message("OrderedList");this.className+=" bxhtmled-button-ordered-list";this.action="insertOrderedList";this.disabledForTextarea=!t.bbCode;this.Create();if(e){e.appendChild(this.GetCont())}}BX.extend(b,t);b.prototype.OnClick=function(){if(!this.disabled){if(!this.editor.bbCode||!this.editor.synchro.IsFocusedOnTextarea()){b.superclass.OnClick.apply(this,arguments)}else{this.editor.GetDialog("InsertList").Show({type:"ol"})}}};function g(t,e){g.superclass.constructor.apply(this,arguments);this.id="unordered-list";this.title=BX.message("UnorderedList");this.className+=" bxhtmled-button-unordered-list";this.action="insertUnorderedList";this.disabledForTextarea=!t.bbCode;this.Create();if(e){e.appendChild(this.GetCont())}}BX.extend(g,t);g.prototype.OnClick=function(){if(!this.disabled){if(!this.editor.bbCode||!this.editor.synchro.IsFocusedOnTextarea()){b.superclass.OnClick.apply(this,arguments)}else{this.editor.GetDialog("InsertList").Show({type:"ul"})}}};function B(t,e){B.superclass.constructor.apply(this,arguments);this.id="indent";this.title=BX.message("Indent");this.className+=" bxhtmled-button-indent";this.action="indent";this.checkableAction=false;this.Create();if(e){e.appendChild(this.GetCont())}}BX.extend(B,t);function X(t,e){X.superclass.constructor.apply(this,arguments);this.id="outdent";this.title=BX.message("Outdent");this.className+=" bxhtmled-button-outdent";this.action="outdent";this.checkableAction=false;this.Create();if(e){e.appendChild(this.GetCont())}}BX.extend(X,t);function v(t,e){v.superclass.constructor.apply(this,arguments);this.id="align-list";this.title=BX.message("BXEdTextAlign");this.posOffset.left=0;this.action="align";var i=this.className;this.className+=" bxhtmled-button-align-left";this.disabledForTextarea=!t.bbCode;this.arValues=[{id:"align_left",title:BX.message("AlignLeft"),className:i+" bxhtmled-button-align-left",action:"align",value:"left"},{id:"align_center",title:BX.message("AlignCenter"),className:i+" bxhtmled-button-align-center",action:"align",value:"center"},{id:"align_right",title:BX.message("AlignRight"),className:i+" bxhtmled-button-align-right",action:"align",value:"right"},{id:"align_justify",title:BX.message("AlignJustify"),className:i+" bxhtmled-button-align-justify",action:"align",value:"justify"}];this.Create();if(e)e.appendChild(this.GetCont())}BX.extend(v,window.BXHtmlEditor.DropDown);v.prototype.SetValue=function(t,e){if(this.disabled){this.SelectItem(null)}else{if(e&&e.value){this.SelectItem("align_"+e.value)}else{this.SelectItem(null)}}};function y(t,e){y.superclass.constructor.apply(this,arguments);this.id="insert-link";this.title=BX.message("InsertLink");this.className+=" bxhtmled-button-link";this.posOffset={top:6,left:0};this.disabledForTextarea=!t.bbCode;this.arValues=[{id:"edit_link",title:BX.message("EditLink"),className:this.className+" bxhtmled-button-link"},{id:"remove_link",title:BX.message("RemoveLink"),className:this.className+" bxhtmled-button-remove-link",action:"removeLink"}];this.Create();if(e){e.appendChild(this.GetCont())}}BX.extend(y,window.BXHtmlEditor.DropDown);y.prototype.OnClick=function(){if(this.disabled)return;if(!this.editor.bbCode||!this.editor.synchro.IsFocusedOnTextarea()){var t,e,i,s=0,a=this.editor.action.CheckState("formatInline",{},"a");if(a){for(t=0;t<a.length;t++){e=a[t];if(e){i=e;s++}if(s>1){break}}}if(s===1&&i){if(this.bOpened){this.Close()}else{this.Open()}}else{this.editor.GetDialog("Link").Show(a,this.savedRange)}}else{this.editor.GetDialog("Link").Show(false,false)}};y.prototype.SelectItem=function(t){if(t=="edit_link"){this.editor.GetDialog("Link").Show(false,this.savedRange)}};function x(t,e){x.superclass.constructor.apply(this,arguments);this.id="image";this.title=BX.message("InsertImage");this.className+=" bxhtmled-button-image";this.disabledForTextarea=!t.bbCode;this.Create();if(e){e.appendChild(this.GetCont())}}BX.extend(x,t);x.prototype.OnClick=function(){if(!this.disabled){this.editor.GetDialog("Image").Show(false,this.savedRange)}};function S(t,e){S.superclass.constructor.apply(this,arguments);this.id="video";this.title=BX.message("BXEdInsertVideo");this.className+=" bxhtmled-button-video";this.disabledForTextarea=!t.bbCode;this.Create();if(e){e.appendChild(this.GetCont())}}BX.extend(S,t);S.prototype.OnClick=function(){if(!this.disabled){this.editor.GetDialog("Video").Show(false,this.savedRange)}};function w(t,e){w.superclass.constructor.apply(this,arguments);this.id="insert-anchor";this.title=BX.message("BXEdAnchor");this.className+=" bxhtmled-button-anchor";this.action="insertAnchor";this.Create();if(e){e.appendChild(this.GetCont())}}BX.extend(w,t);w.prototype.OnClick=function(t){var e=this;if(this.disabled)return;if(!this.pPopup){this.pPopup=new BX.PopupWindow(this.id+"-popup",this.GetCont(),{zIndex:3005,lightShadow:true,offsetTop:4,overlay:{opacity:1},offsetLeft:-128,autoHide:true,closeByEsc:true,className:"bxhtmled-popup",content:""});this.pPopupCont=BX(this.id+"-popup");this.pPopupCont.className="bxhtmled-popup";this.pPopupCont.innerHTML='<div class="bxhtmled-popup-corner"></div>';this.pPopupContWrap=this.pPopupCont.appendChild(BX.create("DIV"));this.pPopupContInput=this.pPopupContWrap.appendChild(BX.create("INPUT",{props:{type:"text",placeholder:BX.message("BXEdAnchorName")+"...",title:BX.message("BXEdAnchorInsertTitle")},style:{width:"150px"}}));this.pPopupContBut=this.pPopupContWrap.appendChild(BX.create("INPUT",{props:{type:"button",value:BX.message("BXEdInsert")},style:{marginLeft:"6px"}}));BX.bind(this.pPopupContInput,"keyup",BX.proxy(this.OnKeyUp,this));BX.bind(this.pPopupContBut,"click",BX.proxy(this.Save,this));BX.addCustomEvent(this.pPopup,"onPopupClose",function(){e.pPopup.destroy();e.pPopup=null})}this.pPopupContInput.value="";this.pPopup.show();BX.focus(this.pPopupContInput)};w.prototype.Save=function(){var t=BX.util.trim(this.pPopupContInput.value);if(t!==""){t=t.replace(/[^ a-z0-9_\-]/gi,"");if(this.savedRange){this.editor.selection.SetBookmark(this.savedRange)}var e=this.editor.phpParser.GetSurrogateNode("anchor",BX.message("BXEdAnchor")+": #"+t,null,{html:"",name:t});this.editor.selection.InsertNode(e);var i=this.editor.util.CheckSurrogateNode(e.parentNode);if(i){this.editor.util.InsertAfter(e,i)}this.editor.selection.SetInvisibleTextAfterNode(e);this.editor.synchro.StartSync(100);if(this.editor.toolbar.controls.More){this.editor.toolbar.controls.More.Close()}}this.pPopup.close()};w.prototype.OnKeyUp=function(t){if(t.keyCode===this.editor.KEY_CODES["enter"]){this.Save()}};function N(t,e){N.superclass.constructor.apply(this,arguments);this.id="insert-table";this.title=BX.message("BXEdTable");this.className+=" bxhtmled-button-table";this.itemClassName="bxhtmled-dd-list-item";this.action="insertTable";this.disabledForTextarea=!t.bbCode;this.PATTERN_ROWS=10;this.PATTERN_COLS=10;this.zIndex=3007;this.posOffset={top:6,left:0};this.Create();if(e){e.appendChild(this.GetCont())}BX.addCustomEvent(this,"OnPopupClose",BX.proxy(this.OnPopupClose,this))}BX.extend(N,window.BXHtmlEditor.DropDown);N.prototype.Create=function(){this.pCont=BX.create("SPAN",{props:{className:this.className,title:this.title},html:"<i></i>"});this.pValuesCont=BX.create("DIV",{props:{className:"bxhtmled-popup bxhtmled-dropdown-cont"},html:'<div class="bxhtmled-popup-corner"></div>'});this.pValuesCont.style.zIndex=this.zIndex;this.valueIndex={};this.pPatternWrap=this.pValuesCont.appendChild(BX.create("DIV"));this.pValuesContWrap=this.pValuesCont.appendChild(BX.create("DIV"));var t=this,e,i,s,a,l=false,o=this.PATTERN_ROWS*this.PATTERN_COLS,n;this.pPatternTbl=this.pPatternWrap.appendChild(BX.create("TABLE",{props:{className:"bxhtmled-pattern-tbl"}}));function r(e,i){var s,a,l;for(s=0;s<t.PATTERN_ROWS;s++){for(a=0;a<t.PATTERN_COLS;a++){l=t.pPatternTbl.rows[s].cells[a];l.className=s<=e&&a<=i?"bxhtmled-td-selected":""}}}BX.bind(this.pPatternTbl,"mousemove",function(t){var e=t.target||t.srcElement;if(a!==e){a=e;if(e.nodeName=="TD"){l=true;r(e.parentNode.rowIndex,e.cellIndex)}else if(e.nodeName=="TABLE"){l=false;r(-1,-1)}}});BX.bind(this.pPatternWrap,"mouseout",function(t){l=false;setTimeout(function(){if(!l){r(-1,-1)}},300)});BX.bind(this.pPatternTbl,"click",function(e){var i=e.target||e.srcElement;if(i.nodeName=="TD"){if(t.editor.action.IsSupported(t.action)){if(t.savedRange){t.editor.selection.SetBookmark(t.savedRange)}t.editor.action.Exec(t.action,{rows:i.parentNode.rowIndex+1,cols:i.cellIndex+1,border:1,cellPadding:1,cellSpacing:1})}if(t.editor.toolbar.controls.More){t.editor.toolbar.controls.More.Close()}t.Close()}});for(e=0;e<o;e++){if(e%this.PATTERN_COLS==0){i=this.pPatternTbl.insertRow(-1)}s=i.insertCell(-1);s.innerHTML="&nbsp;";s.title=s.cellIndex+1+"x"+(i.rowIndex+1)}n=this.pValuesContWrap.appendChild(BX.create("SPAN",{props:{title:BX.message("BXEdInsertTableTitle"),className:this.itemClassName},html:BX.message("BXEdInsertTable")}));BX.bind(n,"mousedown",function(e){t.editor.GetDialog("Table").Show(false,t.savedRange);if(t.editor.toolbar.controls.More){t.editor.toolbar.controls.More.Close()}t.Close()});BX.bind(this.pCont,"click",BX.proxy(this.OnClick,this));BX.bind(this.pCont,"mousedown",BX.delegate(this.OnMouseDown,this))};N.prototype.OnPopupClose=function(){var t=this.editor.toolbar.controls.More;setTimeout(function(){if(t&&t.bOpened){t.CheckOverlay()}},100)};function I(t,e){I.superclass.constructor.apply(this,arguments);this.id="specialchar";this.title=BX.message("BXEdSpecialchar");this.className+=" bxhtmled-button-specialchar";this.itemClassName="bxhtmled-dd-list-item";this.CELLS_COUNT=10;this.posOffset={top:6,left:0};this.zIndex=3007;this.Create();if(e){e.appendChild(this.GetCont())}BX.addCustomEvent(this,"OnPopupClose",BX.proxy(this.OnPopupClose,this))}BX.extend(I,window.BXHtmlEditor.DropDown);I.prototype.Create=function(){this.pCont=BX.create("SPAN",{props:{className:this.className,title:this.title},html:"<i></i>"});this.pValuesCont=BX.create("DIV",{props:{className:"bxhtmled-popup bxhtmled-dropdown-cont"},html:'<div class="bxhtmled-popup-corner"></div>'});this.pValuesCont.style.zIndex=this.zIndex;this.valueIndex={};this.pPatternWrap=this.pValuesCont.appendChild(BX.create("DIV"));this.pValuesContWrap=this.pValuesCont.appendChild(BX.create("DIV"));var t=this.editor.GetLastSpecialchars(),e=this,i,s,a,l=t.length,o;this.pLastChars=this.pPatternWrap.appendChild(BX.create("TABLE",{props:{className:"bxhtmled-last-chars"}}));for(i=0;i<l;i++){if(i%this.CELLS_COUNT==0){s=this.pLastChars.insertRow(-1)}a=s.insertCell(-1)}BX.bind(this.pLastChars,"click",function(t){var i,s=t.target||t.srcElement;if(s.nodeType==3){s=s.parentNode}if(s&&s.getAttribute&&s.getAttribute("data-bx-specialchar")&&e.editor.action.IsSupported("insertHTML")){if(e.savedRange){e.editor.selection.SetBookmark(e.savedRange)}i=s.getAttribute("data-bx-specialchar");e.editor.On("OnSpecialcharInserted",[i]);e.editor.action.Exec("insertHTML",i)}if(e.editor.toolbar.controls.More){e.editor.toolbar.controls.More.Close()}e.Close()});o=this.pValuesContWrap.appendChild(BX.create("SPAN",{props:{title:BX.message("BXEdSpecialcharMoreTitle"),className:this.itemClassName},html:BX.message("BXEdSpecialcharMore")}));BX.bind(o,"mousedown",function(){e.editor.GetDialog("Specialchar").Show(e.savedRange);if(e.editor.toolbar.controls.More){e.editor.toolbar.controls.More.Close()}e.Close()});BX.bind(this.pCont,"click",BX.proxy(this.OnClick,this));BX.bind(this.pCont,"mousedown",BX.delegate(this.OnMouseDown,this))};I.prototype.OnClick=function(){if(this.disabled)return;var t=this.editor.GetLastSpecialchars(),e,i=-1,s=-1,a,l=t.length;for(e=0;e<l;e++){if(e%this.CELLS_COUNT==0){i++;s=-1}s++;a=this.pLastChars.rows[i].cells[s];if(a){a.innerHTML=t[e];a.setAttribute("data-bx-specialchar",t[e]);a.title=BX.message("BXEdSpecialchar")+": "+t[e].substr(1,t[e].length-2)}}I.superclass.OnClick.apply(this,arguments)};I.prototype.OnPopupClose=function(){var t=this.editor.toolbar.controls.More;setTimeout(function(){if(t&&t.bOpened){t.CheckOverlay()}},100)};function E(t,e){E.superclass.constructor.apply(this,arguments);this.id="print_break";this.title=BX.message("BXEdPrintBreak");this.className+=" bxhtmled-button-print-break";this.Create();if(e){e.appendChild(this.GetCont())}}BX.extend(E,t);E.prototype.OnClick=function(){if(this.disabled)return;if(this.editor.action.IsSupported("insertHTML")){if(this.savedRange){this.editor.selection.SetBookmark(this.savedRange)}var t=this.editor.GetIframeDoc(),e=this.editor.SetBxTag(false,{tag:"printbreak",params:{innerHTML:'<span style="display: none">&nbsp;</span>'},name:BX.message("BXEdPrintBreakName"),title:BX.message("BXEdPrintBreakTitle")}),i=BX.create("IMG",{props:{src:this.editor.EMPTY_IMAGE_SRC,id:e,className:"bxhtmled-printbreak",title:BX.message("BXEdPrintBreakTitle")}},t);this.editor.selection.InsertNode(i);var s=this.editor.util.CheckSurrogateNode(i.parentNode);if(s){this.editor.util.InsertAfter(i,s)}this.editor.selection.SetAfter(i);this.editor.Focus();this.editor.synchro.StartSync(100)}if(this.editor.toolbar.controls.More){this.editor.toolbar.controls.More.Close()}};function T(t,e){T.superclass.constructor.apply(this,arguments);this.id="page_break";this.title=BX.message("BXEdPageBreak");this.className+=" bxhtmled-button-page-break";this.Create();if(e){e.appendChild(this.GetCont())}}BX.extend(T,t);T.prototype.OnClick=function(){if(this.savedRange)this.editor.selection.SetBookmark(this.savedRange);var t=this.editor.phpParser.GetSurrogateNode("pagebreak",BX.message("BXEdPageBreakSur"),BX.message("BXEdPageBreakSurTitle"));this.editor.selection.InsertNode(t);var e=this.editor.util.CheckSurrogateNode(t.parentNode);if(e){this.editor.util.InsertAfter(t,e)}this.editor.selection.SelectNode(t);this.NormilizeBreakElement(t);this.editor.selection.SetInvisibleTextAfterNode(t);this.editor.synchro.StartSync(100);if(this.editor.toolbar.controls.More){this.editor.toolbar.controls.More.Close()}};T.prototype.NormilizeBreakElement=function(t){if(t.parentNode&&t.parentNode.nodeName!=="BODY"){var e=this.editor.util.GetNextNotEmptySibling(t),i=this.editor.util.GetPreviousNotEmptySibling(t);if(!e||!i){if(!e)this.editor.util.InsertAfter(t,t.parentNode);if(!i)t.parentNode.parentNode.insertBefore(t,t.parentNode);return this.NormilizeBreakElement(t)}}};function k(t,e){k.superclass.constructor.apply(this,arguments);this.id="hr";this.title=BX.message("BXEdInsertHr");this.className+=" bxhtmled-button-hr";this.Create();if(e){e.appendChild(this.GetCont())}}BX.extend(k,t);function A(t,e){A.superclass.constructor.apply(this,arguments);this.id="spellcheck";this.title=BX.message("BXEdSpellcheck");this.className+=" bxhtmled-button-spell";this.Create();if(e){e.appendChild(this.GetCont())}}BX.extend(A,t);A.prototype.OnClick=function(){if(this.disabled)return;if(this.editor.config.usePspell!=="Y"){alert(BX.message("BXEdNoPspellWarning"))}else{var t=this;if(!window.BXHtmlEditor.Spellchecker)return BX.loadScript(this.editor.config.spellcheck_path,BX.proxy(this.OnClick,this));if(!this.editor.Spellchecker){this.editor.Spellchecker=new window.BXHtmlEditor.Spellchecker(this.editor)}this.editor.GetDialog("Spell").Show(this.savedRange);this.editor.Spellchecker.CheckDocument()}};function P(t,e){P.superclass.constructor.apply(this,arguments);this.id="settings";this.title=BX.message("BXEdSettings");this.className+=" bxhtmled-button-settings";this.disabledForTextarea=false;this.Create();if(e){e.appendChild(this.GetCont())}}BX.extend(P,t);P.prototype.OnClick=function(){this.editor.GetDialog("Settings").Show()};function L(t,e){L.superclass.constructor.apply(this,arguments);this.id="sub";this.title=BX.message("BXEdSub");this.className+=" bxhtmled-button-sub";this.action="sub";this.Create();if(e){e.appendChild(this.GetCont())}}BX.extend(L,t);function O(t,e){O.superclass.constructor.apply(this,arguments);this.id="sup";this.title=BX.message("BXEdSup");this.className+=" bxhtmled-button-sup";this.action="sup";this.Create();if(e){e.appendChild(this.GetCont())}}BX.extend(O,t);function V(t,e){V.superclass.constructor.apply(this,arguments);this.id="fullscreen";this.title=BX.message("BXEdFullscreen");this.className+=" bxhtmled-button-fullscreen";this.action="fullscreen";this.disabledForTextarea=false;this.Create();if(e){e.appendChild(this.GetCont())}}BX.extend(V,t);V.prototype.Check=function(t){this.GetCont().title=t?BX.message("BXEdFullscreenBack"):BX.message("BXEdFullscreen");V.superclass.Check.apply(this,arguments)};function H(t,e){H.superclass.constructor.apply(this,arguments);this.id="smile";this.title=BX.message("BXEdSmile");this.className+=" bxhtmled-button-smile";this.checkableAction=false;this.zIndex=3007;this.posOffset={top:6,left:0};this.smiles=t.config.smiles||[];this.disabledForTextarea=!t.bbCode;this.Create();if(e&&this.smiles.length>0){e.appendChild(this.GetCont())}BX.addCustomEvent(this,"OnPopupClose",BX.proxy(this.OnPopupClose,this))}BX.extend(H,window.BXHtmlEditor.DropDown);H.prototype.CheckBeforeShow=function(){return this.editor.config.smiles&&this.editor.config.smiles.length>0};H.prototype.Create=function(){this.pCont=BX.create("SPAN",{props:{className:this.className,title:this.title},html:"<i></i>"});this.pValuesCont=BX.create("DIV",{props:{className:"bxhtmled-popup bxhtmled-dropdown-cont bxhtmled-smile-cont"},html:'<div class="bxhtmled-popup-corner"></div>'});this.pValuesCont.style.zIndex=this.zIndex;this.valueIndex={};var t=this,e,i;for(e=0;e<this.smiles.length;e++){i=BX.create("IMG",{props:{className:"bxhtmled-smile-img",src:this.smiles[e].path,title:this.smiles[e].name||this.smiles[e].code}});if(this.smiles[e].width){i.style.width=this.smiles[e].width}if(this.smiles[e].height){i.style.height=this.smiles[e].height}i.setAttribute("data-bx-type","action");i.setAttribute("data-bx-action","insertSmile");i.setAttribute("data-bx-value",this.smiles[e].code);BX.bind(i,"error",function(){BX.remove(this)});this.pValuesCont.appendChild(i)}BX.bind(this.pCont,"click",BX.proxy(this.OnClick,this));BX.bind(this.pCont,"mousedown",BX.delegate(this.OnMouseDown,this));BX.bind(this.pValuesCont,"mousedown",function(e){t.editor.CheckCommand(e.target||e.srcElement);t.Close()})};N.prototype.OnPopupClose=function(){var t=this.editor.toolbar.controls.More;setTimeout(function(){if(t&&t.bOpened){t.CheckOverlay()}},100)};function D(t,e){D.superclass.constructor.apply(this,arguments);this.id="quote";this.title=BX.message("BXEdQuote");this.className+=" bxhtmled-button-quote";this.action="quote";this.disabledForTextarea=!t.bbCode;this.Create();if(e){e.appendChild(this.GetCont())}}BX.extend(D,t);D.prototype.OnMouseDown=function(){this.editor.action.actions.quote.setExternalSelection(false);this.editor.action.actions.quote.setRange(false);var t=this.editor.selection.GetRange(this.editor.selection.GetSelection(document));if(!this.editor.synchro.IsFocusedOnTextarea()&&this.editor.iframeView.isFocused){this.savedRange=this.editor.selection.SaveBookmark();this.editor.action.actions.quote.setRange(this.savedRange)}if((this.editor.synchro.IsFocusedOnTextarea()||!this.editor.iframeView.isFocused||this.savedRange.collapsed)&&t&&!t.collapsed){var e=BX.create("DIV",{html:t.toHtml()},this.editor.GetIframeDoc());this.editor.action.actions.quote.setExternalSelection(this.editor.util.GetTextContentEx(e));BX.remove(e)}D.superclass.OnMouseDown.apply(this,arguments)};function R(t,e){R.superclass.constructor.apply(this,arguments);this.id="code";this.title=BX.message("BXEdCode");this.className+=" bxhtmled-button-code";this.action="code";this.disabledForTextarea=!t.bbCode;this.lastStatus=null;this.allowedControls=["SearchButton","ChangeView","Undo","Redo","RemoveFormat","TemplateSelector","InsertChar","Settings","Fullscreen","Spellcheck","Code","More","BbCode"];this.Create();if(e){e.appendChild(this.GetCont())}}BX.extend(R,t);R.prototype.SetValue=function(t,e,i){if(this.lastStatus!==t){var s=this.editor.toolbar;for(var a in s.controls){if(s.controls.hasOwnProperty(a)&&typeof s.controls[a].Disable=="function"&&!BX.util.in_array(a,this.allowedControls)){s.controls[a].Disable(t)}}}this.lastStatus=t;this.Check(t)};function F(t,e){F.superclass.constructor.apply(this,arguments);this.id="more";this.title=BX.message("BXEdMore");this.className+=" bxhtmled-button-more";this.Create();this.posOffset.left=-8;BX.addClass(this.pValuesContWrap,"bxhtmled-more-cnt");this.disabledForTextarea=false;if(e){e.appendChild(this.GetCont())}var i=this;BX.bind(this.pValuesContWrap,"click",function(t){var e=t.target||t.srcElement,s=e&&e.getAttribute?e.getAttribute("data-bx-type"):false;i.editor.CheckCommand(e)})}BX.extend(F,window.BXHtmlEditor.DropDown);F.prototype.Open=function(){this.pValuesCont.style.width="";F.superclass.Open.apply(this,arguments);var t=this.GetPopupBindCont(),e=BX.pos(t),i=Math.round(e.left-this.pValuesCont.offsetWidth/2+t.offsetWidth/2+this.posOffset.left);this.pValuesCont.style.width=this.pValuesCont.offsetWidth+"px";this.pValuesCont.style.left=i+"px"};F.prototype.GetPopupCont=function(){return this.pValuesContWrap};F.prototype.CheckClose=function(t){if(!this.bOpened){return BX.unbind(document,"mousedown",BX.proxy(this.CheckClose,this))}var e;if(t.target)e=t.target;else if(t.srcElement)e=t.srcElement;if(e.nodeType==3)e=e.parentNode;if(e.style.zIndex>this.zIndex){this.CheckOverlay()}else if(!BX.findParent(e,{className:"bxhtmled-popup"})){this.Close()}};F.prototype.CheckOverlay=function(){var t=this;this.editor.overlay.Show({zIndex:this.zIndex-1}).onclick=function(){t.Close()}};function z(t,e){e={id:"bx_image",width:700,resizable:false,className:"bxhtmled-img-dialog"};this.id="image";this.action="insertImage";this.loremIpsum=BX.message("BXEdLoremIpsum")+"\n"+BX.message("BXEdLoremIpsum");z.superclass.constructor.apply(this,[t,e]);this.readyToShow=false;if(!this.editor.fileDialogsLoaded){var i=this;this.editor.LoadFileDialogs(function(){i.SetContent(i.Build());i.readyToShow=true})}else{this.SetContent(this.Build());this.readyToShow=true}BX.addCustomEvent(this,"OnDialogSave",BX.proxy(this.Save,this))}BX.extend(z,e);z.prototype.Build=function(){function t(t,e,i){var s,a,l;s=t.insertRow(-1);if(i){s.className="bxhtmled-add-row"}a=s.insertCell(-1);a.className="bxhtmled-left-c";if(e&&e.label){a.appendChild(BX.create("LABEL",{props:{className:e.required?"bxhtmled-req":""},text:e.label})).setAttribute("for",e.id)}l=s.insertCell(-1);l.className="bxhtmled-right-c";return{row:s,leftCell:a,rightCell:l}}var e=this,i,s;this.pCont=BX.create("DIV",{props:{className:"bxhtmled-img-dialog-cnt"}});var a=BX.create("TABLE",{props:{className:"bxhtmled-dialog-tbl bxhtmled-img-dialog-tbl"}});i=a.insertRow(-1);i.className="bxhtmled-img-preview-row";s=BX.adjust(i.insertCell(-1),{props:{colSpan:2,className:"bxhtmled-img-prev-c"}});this.pPreview=s.appendChild(BX.create("DIV",{props:{className:"bxhtmled-img-preview"+(this.editor.bbCode?" bxhtmled-img-preview-bb":""),id:this.id+"-preview"},html:this.editor.bbCode?"":this.loremIpsum}));this.pPreviewRow=i;i=t(a,{label:BX.message("BXEdImgSrc")+":",id:this.id+"-src",required:true});this.pSrc=i.rightCell.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-src",className:"bxhtmled-80-input"}}));this.pSrc.placeholder=BX.message("BXEdImgSrcRequired");BX.bind(this.pSrc,"blur",BX.proxy(this.SrcOnChange,this));BX.bind(this.pSrc,"change",BX.proxy(this.SrcOnChange,this));BX.bind(this.pSrc,"keyup",BX.proxy(this.SrcOnChange,this));this.firstFocus=this.pSrc;if(!this.editor.bbCode){var l=BX("bx-open-file-medialib-but-"+this.editor.id);if(l){i.rightCell.appendChild(l)}else{var o=BX("bx_open_file_medialib_button_"+this.editor.id);if(o){i.rightCell.appendChild(o);BX.bind(o,"click",window["BxOpenFileBrowserImgFile"+this.editor.id])}else{var n=BX("bx_ml_bx_open_file_medialib_button_"+this.editor.id);if(n){i.rightCell.appendChild(n)}}}}else{l=BX("bx-open-file-medialib-but-"+this.editor.id);o=BX("bx_open_file_medialib_button_"+this.editor.id);if(l){l.style.display="none"}if(o){o.style.display="none"}}i=t(a,{label:BX.message("BXEdImgSize")+":",id:this.id+"-size"});i.rightCell.appendChild(this.GetSizeControl());BX.addClass(i.leftCell,"bxhtmled-left-c-top");i.leftCell.style.paddingTop="12px";this.pSizeRow=i.row;if(!this.editor.bbCode){i=t(a,{label:BX.message("BXEdImgTitle")+":",id:this.id+"-title"});this.pTitle=i.rightCell.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-title",className:"bxhtmled-90-input"}}))}i=a.insertRow(-1);var r=i.insertCell(-1);BX.adjust(r,{props:{className:"bxhtmled-title-cell bxhtmled-title-cell-foldable",colSpan:2},text:BX.message("BXEdLinkAdditionalTitle")});r.onclick=function(){e.ShowRows(["align","style","alt","link"],true,!e.bAdditional);e.bAdditional=!e.bAdditional};if(!this.editor.bbCode){i=t(a,{label:BX.message("BXEdImgAlign")+":",id:this.id+"-align"});this.pAlign=i.rightCell.appendChild(BX.create("SELECT",{props:{id:this.id+"-align"}}));this.pAlign.options.add(new Option(BX.message("BXEdImgAlignNone"),"",true,true));this.pAlign.options.add(new Option(BX.message("BXEdImgAlignTop"),"top",true,true));this.pAlign.options.add(new Option(BX.message("BXEdImgAlignLeft"),"left",true,true));this.pAlign.options.add(new Option(BX.message("BXEdImgAlignRight"),"right",true,true));this.pAlign.options.add(new Option(BX.message("BXEdImgAlignBottom"),"bottom",true,true));this.pAlign.options.add(new Option(BX.message("BXEdImgAlignMiddle"),"middle",true,true));BX.bind(this.pAlign,"change",BX.delegate(this.ShowPreview,this));this.pAlignRow=i.row}if(!this.editor.bbCode){i=t(a,{label:BX.message("BXEdImgAlt")+":",id:this.id+"-alt"});this.pAlt=i.rightCell.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-alt",className:"bxhtmled-90-input"}}));this.pAltRow=i.row}if(!this.editor.bbCode){i=t(a,{label:BX.message("BXEdCssClass")+":",id:this.id+"-style"},true);this.pClass=i.rightCell.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-style"}}));this.pStyleRow=i.row}i=t(a,{label:BX.message("BXEdImgLinkOnImage")+":",id:this.id+"-link"});this.pLink=i.rightCell.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-link",className:"bxhtmled-80-input"}}));this.pEditLinkBut=i.rightCell.appendChild(BX.create("SPAN",{props:{className:"bxhtmled-top-bar-btn bxhtmled-button-link",title:BX.message("EditLink")},html:"<i></i>"}));BX.bind(this.pEditLinkBut,"click",function(){if(BX.util.trim(e.pSrc.value)==""){BX.focus(e.pSrc)}else{var t=e.pLink.value;e.pLink.value="bx-temp-link-href";e.Save();e.oDialog.Close();var i,s,a=e.editor.GetIframeDoc().getElementsByTagName("A");for(i=0;i<a.length;i++){var l=a[i].getAttribute("href");if(l=="bx-temp-link-href"){s=a[i];s.setAttribute("href",t);e.editor.selection.SelectNode(s);e.editor.GetDialog("Link").Show([s]);break}}}});this.pCont.appendChild(a);this.pLinkRow=i.row;if(!this.editor.bbCode){window["OnFileDialogImgSelect"+this.editor.id]=function(t,i,s){var a;if(typeof t=="object"){a=t.src;if(e.pTitle)e.pTitle.value=t.description||t.name;if(e.pAlt)e.pAlt.value=t.description||t.name}else{a=(i=="/"?"":i)+"/"+t}e.pSrc.value=a;BX.focus(e.pSrc);e.pSrc.select();e.SrcOnChange()}}this.rows={preview:{cont:this.pPreviewRow,height:200},size:{cont:this.pSizeRow,height:68},align:{cont:this.pAlignRow,height:36},style:{cont:this.pStyleRow,height:36},alt:{cont:this.pAltRow,height:36},link:{cont:this.pLinkRow,height:36}};return this.pCont};z.prototype.GetSizeControl=function(){var t,e,i=this,s,a,l=[100,90,80,70,60,50,40,30,20],o=BX.create("DIV"),n=o.appendChild(BX.create("SPAN",{props:{className:"bxhtmled-size-perc"}}));this.percVals=l;this.pPercWrap=n;this.pSizeCont=o;this.oSize={};BX.bind(n,"click",function(t){var e=t.target||t.srcElement;if(e){var s=parseInt(e.getAttribute("data-bx-size-val"),10);if(s){i.SetPercentSize(s,true)}}});function r(t){var e=0,s,a=t.target||t.srcElement;if(a!==n){a=BX.findParent(a,function(t){e++;return t==n||e>3},n)}if(a!==n){i.SetPercentSize(i.savedPerc,false);if(i.sizeControlChecker){BX.unbind(document,"mousemove",r);i.sizeControlChecker=false}}}BX.bind(n,"mouseover",function(t){var e,a=t.target||t.srcElement;if(!i.sizeControlChecker){BX.bind(document,"mousemove",r);i.sizeControlChecker=true}e=parseInt(a.getAttribute("data-bx-size-val"),10);i.overPerc=e>0;if(i.overPerc){i.SetPercentSize(e,false)}else{if(s){clearTimeout(s)}s=setTimeout(function(){if(!i.overPerc){i.SetPercentSize(i.savedPerc,false);if(i.sizeControlChecker){BX.unbind(document,"mousemove",r);i.sizeControlChecker=false}}},200)}});BX.bind(n,"mouseout",function(t){var e,a=t.target||t.srcElement;if(s){clearTimeout(s)}s=setTimeout(function(){if(!i.overPerc){i.SetPercentSize(i.savedPerc,false);if(i.sizeControlChecker){BX.unbind(document,"mousemove",r);i.sizeControlChecker=false}}},200)});function h(){var e=parseInt(i.pWidth.value);if(!isNaN(e)&&t!=e){if(!i.sizeRatio&&i.originalWidth&&i.originalHeight){i.sizeRatio=i.originalWidth/i.originalHeight}if(i.sizeRatio){i.pHeight.value=Math.round(e/i.sizeRatio);t=e;i.ShowPreview()}}}function d(){var t=parseInt(i.pHeight.value);if(!isNaN(t)&&e!=t){if(!i.sizeRatio&&i.originalWidth&&i.originalHeight){i.sizeRatio=i.originalWidth/i.originalHeight}if(i.sizeRatio){i.pWidth.value=parseInt(t*i.sizeRatio);e=t;i.ShowPreview()}}}o.appendChild(BX.create("LABEL",{text:BX.message("BXEdImgWidth")+": "})).setAttribute("for",this.id+"-width");this.pWidth=o.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-width"},style:{width:"40px",marginBottom:"4px"}}));o.appendChild(BX.create("LABEL",{style:{marginLeft:"20px"},text:BX.message("BXEdImgHeight")+": "})).setAttribute("for",this.id+"-height");this.pHeight=o.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-height"},style:{width:"40px",marginBottom:"4px"}}));this.pNoSize=o.appendChild(BX.create("INPUT",{props:{type:"checkbox",id:this.id+"-no-size",className:"bxhtmled-img-no-size-ch"}}));o.appendChild(BX.create("LABEL",{props:{className:"bxhtmled-img-no-size-lbl"},text:BX.message("BXEdImgNoSize")})).setAttribute("for",this.id+"-no-size");BX.bind(this.pNoSize,"click",BX.proxy(this.NoSizeCheck,this));BX.bind(this.pWidth,"blur",h);BX.bind(this.pWidth,"change",h);BX.bind(this.pWidth,"keyup",h);BX.bind(this.pHeight,"blur",d);BX.bind(this.pHeight,"change",d);BX.bind(this.pHeight,"keyup",d);for(a=0;a<l.length;a++){n.appendChild(BX.create("SPAN",{props:{className:"bxhtmled-size-perc-i"},attrs:{"data-bx-size-val":l[a]},html:l[a]+"%"}))}return o};z.prototype.NoSizeCheck=function(){
if(this.pNoSize.checked){BX.addClass(this.pSizeCont,"bxhtmled-img-no-size-cont");this.pSizeRow.cells[0].style.height=this.pSizeRow.cells[1].style.height=""}else{BX.removeClass(this.pSizeCont,"bxhtmled-img-no-size-cont")}this.ShowPreview()};z.prototype.SetPercentSize=function(t,e){var i,s,a="bxhtmled-size-perc-i-active";if(e){for(s=0;s<this.pPercWrap.childNodes.length;s++){i=this.pPercWrap.childNodes[s];if(t&&i.getAttribute("data-bx-size-val")==t){BX.addClass(i,a)}else{BX.removeClass(i,a)}}}if(t!==false){t=t/100;this.pWidth.value=Math.round(this.originalWidth*t)||"";this.pHeight.value=Math.round(this.originalHeight*t)||""}else if(this.savedWidth&&this.savedHeight){this.pWidth.value=this.savedWidth;this.pHeight.value=this.savedHeight}this.ShowPreview();if(e){this.savedWidth=this.pWidth.value;this.savedHeight=this.pHeight.value;this.savedPerc=t!==false?(t||1)*100:false}};z.prototype.SrcOnChange=function(t){var e,i,s,a,l,o=this,n=this.pSrc.value;t=t!==false;if(this.lastSrc!==n){this.lastSrc=n;if(!this.pInvisCont){this.pInvisCont=this.pCont.appendChild(BX.create("DIV",{props:{className:"bxhtmled-invis-cnt"}}))}else{BX.cleanNode(this.pInvisCont)}this.dummyImg=this.pInvisCont.appendChild(BX.create("IMG"));BX.bind(this.dummyImg,"load",function(){setTimeout(function(){o.originalWidth=o.dummyImg.offsetWidth;o.originalHeight=o.dummyImg.offsetHeight;if(t){o.pWidth.value=o.originalWidth;o.pHeight.value=o.originalHeight;i=100}else{i=false;a=Math.round(1e4*parseInt(o.pWidth.value)/parseInt(o.originalWidth))/100;l=Math.round(1e4*parseInt(o.pHeight.value)/parseInt(o.originalHeight))/100;if(Math.abs(a-l)<=.1){s=(a+l)/2;for(e=0;e<o.percVals.length;e++){if(Math.abs(o.percVals[e]-s)<=.1){i=o.percVals[e];break}}}}o.sizeRatio=o.originalWidth/o.originalHeight;o.SetPercentSize(i,true);if(o.bEmptySrcRowsHidden){o.ShowRows(o.bAdditional?["preview","size","align","style","alt"]:["preview","size"],true,true);o.bEmptySrcRowsHidden=false}o.ShowPreview()},100)});BX.bind(this.dummyImg,"error",function(){o.pWidth.value="";o.pHeight.value=""});this.dummyImg.src=n}};z.prototype.ShowPreview=function(){if(!this.pPreviewImg){this.pPreviewImg=BX.create("IMG");if(this.pAlign){this.pPreview.insertBefore(this.pPreviewImg,this.pPreview.firstChild)}else{this.pPreview.appendChild(this.pPreviewImg)}}if(this.pPreviewImg.src!=this.pSrc.value){this.pPreviewImg.src=this.pSrc.value}if(this.pNoSize.checked){this.pPreviewImg.style.width="";this.pPreviewImg.style.height=""}else{this.pPreviewImg.style.width=this.pWidth.value+"px";this.pPreviewImg.style.height=this.pHeight.value+"px"}if(this.pAlign){var t=this.pAlign.value;if(t!=this.pPreviewImg.align){if(t==""){this.pPreviewImg.removeAttribute("align")}else{this.pPreviewImg.align=t}}}};z.prototype.SetValues=function(t){if(!t){t={}}var e,i,s=["preview","size","align","style","alt"];this.lastSrc="";this.bEmptySrcRowsHidden=this.bNewImage;if(this.bNewImage){for(e=0;e<s.length;e++){i=this.rows[s[e]];if(i&&i.cont){i.cont.style.display="none";this.SetRowHeight(i.cont,0,0)}}}else{for(e=0;e<s.length;e++){i=this.rows[s[e]];if(i&&i.cont){i.cont.style.display="";this.SetRowHeight(i.cont,i.height,100)}}}this.pSrc.value=t.src||"";if(this.pTitle)this.pTitle.value=t.title||"";if(this.pAlt)this.pAlt.value=t.alt||"";this.savedWidth=this.pWidth.value=t.width||"";this.savedHeight=this.pHeight.value=t.height||"";if(this.pAlign)this.pAlign.value=t.align||"";if(this.pClass)this.pClass.value=t.className||"";this.pLink.value=t.link||"";this.pNoSize.checked=t.noWidth&&t.noHeight;this.NoSizeCheck();this.ShowRows(["align","style","alt","link"],false,false);this.bAdditional=false;this.SrcOnChange(!t.width||!t.height);if(this.pClass){if(!this.oClass){this.oClass=new window.BXHtmlEditor.ClassSelector(this.editor,{id:this.id+"-class-selector",input:this.pClass,filterTag:"IMG",value:this.pClass.value});var a=this;BX.addCustomEvent(this.oClass,"OnComboPopupClose",function(){a.closeByEnter=true});BX.addCustomEvent(this.oClass,"OnComboPopupOpen",function(){a.closeByEnter=false})}else{this.oClass.OnChange()}}};z.prototype.GetValues=function(){var t={src:this.pSrc.value,width:this.pNoSize.checked?"":this.pWidth.value,height:this.pNoSize.checked?"":this.pHeight.value,link:this.pLink.value||"",image:this.image||false};if(this.pTitle)t.title=this.pTitle.value;if(this.pAlt)t.alt=this.pAlt.value;if(this.pAlign)t.align=this.pAlign.value;if(this.pClass)t.className=this.pClass.value||"";return t};z.prototype.Show=function(t,e){var i=this,s,a={},l,o,n=false;if(!this.readyToShow){return setTimeout(function(){i.Show(t,e)},100)}this.savedRange=e;if(!this.editor.bbCode||!this.editor.synchro.IsFocusedOnTextarea()){if(!this.editor.iframeView.IsFocused()){this.editor.iframeView.Focus()}if(this.savedRange){this.editor.selection.SetBookmark(this.savedRange)}if(!t){s=this.editor.selection.GetRange();t=s.getNodes([1])}}if(t){for(o=0;o<t.length;o++){n=t[o];l=this.editor.GetBxTag(n);if(l.tag||!n.nodeName||n.nodeName!="IMG"){n=false}else{break}}}this.bNewImage=!n;this.image=n;if(n){a.src=n.getAttribute("src");if(n.style.width){a.width=n.style.width}if(!a.width&&n.getAttribute("width")){a.width=n.getAttribute("width")}if(!a.width){a.width=n.offsetWidth;a.noWidth=true}if(n.style.height){a.height=n.style.height}if(!a.height&&n.getAttribute("height")){a.height=n.getAttribute("height")}if(!a.height){a.height=n.offsetHeight;a.noHeight=true}var r=n.getAttribute("data-bx-clean-attribute");if(r){n.removeAttribute(r);n.removeAttribute("data-bx-clean-attribute")}a.alt=n.alt||"";a.title=n.title||"";a.title=n.title||"";a.className=n.className;a.align=n.align||"";var h=n.parentNode.nodeName=="A"?n.parentNode:null;if(h&&h.href){a.link=h.getAttribute("href")}}if(!this.editor.bbCode){window["OnFileDialogSelect"+this.editor.id]=window["OnFileDialogImgSelect"+this.editor.id]=function(t,e,s){var a;if(typeof t=="object"){a=t.src;if(i.pTitle)i.pTitle.value=t.description||t.name;if(i.pAlt)i.pAlt.value=t.description||t.name}else{a=(e=="/"?"":e)+"/"+t}i.pSrc.value=a;BX.focus(i.pSrc);i.pSrc.select();i.SrcOnChange()}}this.SetValues(a);this.SetTitle(BX.message("InsertImage"));z.superclass.Show.apply(this,arguments)};z.prototype.SetPanelHeight=function(t,e){this.pSearchCont.style.height=t+"px";this.pSearchCont.style.opacity=e/100;this.editor.SetAreaContSize(this.origAreaWidth,this.origAreaHeight-t,{areaContTop:this.editor.toolbar.GetHeight()+t})};z.prototype.ShowRows=function(t,e,i){var s=this,a,l,o,n,r,h;if(e){for(r=0;r<t.length;r++){h=this.rows[t[r]];if(h&&h.cont){if(h.animation)h.animation.stop();h.cont.style.display="";if(i){a=0;l=h.height;o=0;n=100}else{a=h.height;l=0;o=100;n=0}h.animation=new BX.easing({_row:h,duration:300,start:{height:a,opacity:o},finish:{height:l,opacity:n},transition:BX.easing.makeEaseOut(BX.easing.transitions.quart),step:function(t){s.SetRowHeight(this._row.cont,t.height,t.opacity)},complete:function(){this._row.animation=null}});h.animation.animate()}}}else{for(r=0;r<t.length;r++){h=this.rows[t[r]];if(h&&h.cont){if(i){h.cont.style.display="";this.SetRowHeight(h.cont,h.height,100)}else{h.cont.style.display="none";this.SetRowHeight(h.cont,0,0)}}}}};z.prototype.SetRowHeight=function(t,e,i){if(t&&t.cells){if(e==0||i==0){t.style.display="none"}else{t.style.display=""}t.style.opacity=i/100;for(var s=0;s<t.cells.length;s++){t.cells[s].style.height=e+"px"}}};function _(t,e){e={id:"bx_link",width:600,resizable:false,className:"bxhtmled-link-dialog"};_.superclass.constructor.apply(this,[t,e]);this.id="link"+this.editor.id;this.action="createLink";this.selectFirstFocus=true;this.readyToShow=false;if(!this.editor.fileDialogsLoaded){var i=this;this.editor.LoadFileDialogs(function(){i.SetContent(i.Build());i.ChangeType();i.readyToShow=true})}else{this.SetContent(this.Build());this.ChangeType();this.readyToShow=true}BX.addCustomEvent(this,"OnDialogSave",BX.proxy(this.Save,this))}BX.extend(_,e);_.prototype.Build=function(){function t(t,e,i){var s,a,l;s=t.insertRow(-1);if(i){s.className="bxhtmled-add-row"}a=s.insertCell(-1);a.className="bxhtmled-left-c";if(e&&e.label){a.appendChild(BX.create("LABEL",{text:e.label})).setAttribute("for",e.id)}l=s.insertCell(-1);l.className="bxhtmled-right-c";return{row:s,leftCell:a,rightCell:l}}var e=this,i,s=BX.create("DIV");var a=BX.create("TABLE",{props:{className:"bxhtmled-dialog-tbl bxhtmled-dialog-tbl-collapsed"}});if(!this.editor.bbCode){i=t(a,{label:BX.message("BXEdLinkType")+":",id:this.id+"-type"});this.pType=i.rightCell.appendChild(BX.create("SELECT",{props:{id:this.id+"-type"}}));this.pType.options.add(new Option(BX.message("BXEdLinkTypeInner"),"internal",true,true));this.pType.options.add(new Option(BX.message("BXEdLinkTypeOuter"),"external",false,false));this.pType.options.add(new Option(BX.message("BXEdLinkTypeAnchor"),"anchor",false,false));this.pType.options.add(new Option(BX.message("BXEdLinkTypeEmail"),"email",false,false));BX.bind(this.pType,"change",BX.delegate(this.ChangeType,this))}i=t(a,{label:BX.message("BXEdLinkText")+":",id:this.id+"-text"});this.pText=i.rightCell.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-text",placeholder:BX.message("BXEdLinkTextPh")}}));this.pTextCont=i.row;i=t(a,{label:BX.message("BXEdLinkInnerHtml")+":",id:this.id+"-innerhtml"});this.pInnerHtml=i.rightCell.appendChild(BX.create("DIV",{props:{className:"bxhtmled-ld-html-wrap"}}));this.pInnerHtmlCont=i.row;this.firstFocus=this.pText;i=t(a,{label:BX.message("BXEdLinkHref")+":",id:this.id+"-href"});this.pHrefIn=i.rightCell.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-href",placeholder:BX.message("BXEdLinkHrefPh")}}));if(!this.editor.bbCode){this.pHrefIn.style.minWidth="80%";var l=BX("bx-open-file-link-medialib-but-"+this.editor.id);if(l){i.rightCell.appendChild(l)}else{var o=BX("bx_open_file_link_medialib_button_"+this.editor.id);if(o){i.rightCell.appendChild(o);BX.bind(o,"click",window["BxOpenFileBrowserImgFile"+this.editor.id])}else{var n=BX("bx_ml_bx_open_file_link_medialib_button_"+this.editor.id);if(n){i.rightCell.appendChild(n)}}}}else{l=BX("bx-open-file-link-medialib-but-"+this.editor.id);o=BX("bx_open_file_link_medialib_button_"+this.editor.id);if(l){l.style.display="none"}if(o){o.style.display="none"}}this.pHrefIntCont=i.row;i=t(a,{label:BX.message("BXEdLinkHref")+":",id:this.id+"-href-ext"});this.pHrefType=i.rightCell.appendChild(BX.create("SELECT",{props:{id:this.id+"-href-type"}}));this.pHrefType.options.add(new Option("http://","http://",false,false));this.pHrefType.options.add(new Option("https://","https://",false,false));this.pHrefType.options.add(new Option("ftp://","ftp://",false,false));this.pHrefType.options.add(new Option("","",false,false));this.pHrefExt=i.rightCell.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-href-ext",placeholder:BX.message("BXEdLinkHrefExtPh")},style:{minWidth:"250px"}}));this.pHrefExtCont=i.row;i=t(a,{label:BX.message("BXEdLinkHrefAnch")+":",id:this.id+"-href-anch"});this.pHrefAnchor=i.rightCell.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-href-anchor",placeholder:BX.message("BXEdLinkSelectAnchor")}}));this.pHrefAnchCont=i.row;i=t(a,{label:BX.message("BXEdLinkHrefEmail")+":",id:this.id+"-href-email"});var r=BX.browser.IsIE()||BX.browser.IsIE9()?"text":"email";this.pHrefEmail=i.rightCell.appendChild(BX.create("INPUT",{props:{type:r,id:this.id+"-href-email"}}));this.pHrefEmailCont=i.row;if(!this.editor.bbCode){i=a.insertRow(-1);var h=i.insertCell(-1);BX.adjust(h,{props:{className:"bxhtmled-title-cell bxhtmled-title-cell-foldable",colSpan:2},text:BX.message("BXEdLinkAdditionalTitle")});h.onclick=function(){BX.toggleClass(a,"bxhtmled-dialog-tbl-collapsed")};i=t(a,false,true);this.pStatCont=i.row;this.pStat=i.leftCell.appendChild(BX.create("INPUT",{props:{type:"checkbox",id:this.id+"-stat"}}));i.rightCell.appendChild(BX.create("LABEL",{text:BX.message("BXEdLinkStat")})).setAttribute("for",this.id+"-stat");var d,p=i.rightCell.appendChild(BX.create("DIV",{props:{className:"bxhtmled-stat-wrap"}}));d=p.appendChild(BX.create("DIV",{html:'<label for="event1">'+BX.message("BXEdLinkStatEv1")+":</label> "}));this.pStatEvent1=d.appendChild(BX.create("INPUT",{props:{type:"text",id:"event1"},style:{minWidth:"50px"}}));d=p.appendChild(BX.create("DIV",{html:'<label for="event2">'+BX.message("BXEdLinkStatEv2")+":</label> "}));this.pStatEvent2=d.appendChild(BX.create("INPUT",{props:{type:"text",id:"event2"},style:{minWidth:"50px"}}));d=p.appendChild(BX.create("DIV",{html:'<label for="event3">'+BX.message("BXEdLinkStatEv3")+":</label> "}));this.pStatEvent3=d.appendChild(BX.create("INPUT",{props:{type:"text",id:"event3"},style:{minWidth:"50px"}}));BX.addClass(i.leftCell,"bxhtmled-left-c-top");BX.bind(this.pStat,"click",BX.delegate(this.CheckShowStatParams,this));i=t(a,{label:BX.message("BXEdLinkTitle")+":",id:this.id+"-title"},true);this.pTitle=i.rightCell.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-title"}}));i=t(a,{label:BX.message("BXEdCssClass")+":",id:this.id+"-style"},true);this.pClass=i.rightCell.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-style"}}));i=t(a,{label:BX.message("BXEdLinkTarget")+":",id:this.id+"-target"},true);this.pTarget=i.rightCell.appendChild(BX.create("SELECT",{props:{id:this.id+"-target"}}));this.pTarget.options.add(new Option(BX.message("BXEdLinkTargetBlank"),"_blank",false,false));this.pTarget.options.add(new Option(BX.message("BXEdLinkTargetParent"),"_parent",false,false));this.pTarget.options.add(new Option(BX.message("BXEdLinkTargetSelf"),"_self",true,true));this.pTarget.options.add(new Option(BX.message("BXEdLinkTargetTop"),"_top",false,false));i=t(a,false,true);this.pNoindex=i.leftCell.appendChild(BX.create("INPUT",{props:{type:"checkbox",id:this.id+"-noindex"}}));i.rightCell.appendChild(BX.create("LABEL",{text:BX.message("BXEdLinkNoindex")})).setAttribute("for",this.id+"-noindex");BX.bind(this.pNoindex,"click",BX.delegate(this.CheckNoindex,this));i=t(a,{label:BX.message("BXEdLinkId")+":",id:this.id+"-id"},true);this.pId=i.rightCell.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-id"}}));i=t(a,{label:BX.message("BXEdLinkRel")+":",id:this.id+"-rel"},true);this.pRel=i.rightCell.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-rel"}}))}s.appendChild(a);return s};_.prototype.OpenFileDialog=function(){var t=window["BxOpenFileBrowserWindFile"+this.editor.id];if(t&&typeof t=="function"){var e=this;window["OnFileDialogSelect"+this.editor.id]=function(t,i,s){e.pHrefIn.value=(i=="/"?"":i)+"/"+t;e.pHrefIn.focus();e.pHrefIn.select();window["OnFileDialogSelect"+e.editor.id]=null};t()}};_.prototype.ChangeType=function(){var t=this.pType?this.pType.value:"internal";this.pHrefIntCont.style.display="none";this.pHrefExtCont.style.display="none";this.pHrefAnchCont.style.display="none";this.pHrefEmailCont.style.display="none";if(this.pStatCont)this.pStatCont.style.display="none";if(t=="internal"){this.pHrefIntCont.style.display=""}else if(t=="external"){if(this.pStatCont)this.pStatCont.style.display="";this.pHrefExtCont.style.display=""}else if(t=="anchor"){this.pHrefAnchCont.style.display=""}else if(t=="email"){this.pHrefEmailCont.style.display=""}};_.prototype.CheckShowStatParams=function(){if(this.pStat.checked){BX.removeClass(this.pStatCont,"bxhtmled-link-stat-hide")}else{BX.addClass(this.pStatCont,"bxhtmled-link-stat-hide")}};_.prototype.CheckNoindex=function(){if(this.pNoindex.checked){this.pRel.value="nofollow";this.pRel.disabled=true}else{this.pRel.value=this.pRel.value=="nofollow"?"":this.pRel.value;this.pRel.disabled=false}};_.prototype.SetValues=function(t){this.pHrefAnchor.value="";if(!this.editor.bbCode){this.pStatEvent1.value=this.pStatEvent2.value=this.pStatEvent3.value="";this.pStat.checked=false}if(!t){t={}}else{var e=t.href||"";if(this.editor.bbCode){t.type="internal";this.pHrefIn.value=e||"";this.firstFocus=this.pHrefIn}else if(e!=""){if(e.substring(0,"mailto:".length).toLowerCase()=="mailto:"){t.type="email";this.pHrefEmail.value=e.substring("mailto:".length)}else if(e.substr(0,1)=="#"){t.type="anchor";this.pHrefAnchor.value=e;this.firstFocus=this.pHrefAnchor}else if(e.indexOf("://")!==-1||e.substr(0,"www.".length)=="www."||e.indexOf("&goto=")!==-1){t.type="external";if(e.substr(0,"/bitrix/redirect.php".length)=="/bitrix/redirect.php"){this.pStat.checked=true;this.CheckShowStatParams();var i=e.substring("/bitrix/redirect.php".length);function s(t,e){var i=e.indexOf(t+"=");if(i<0){return""}var s=e.indexOf("&",i+t.length+1);if(s<0){e=e.substring(i+t.length+1)}else{e=e.substr(i+t.length+1,s-i-1-t.length)}return unescape(e)}this.pStatEvent1.value=s("event1",i);this.pStatEvent2.value=s("event2",i);this.pStatEvent3.value=s("event3",i);e=s("goto",i)}if(e.substr(0,"www.".length)=="www.")e="http://"+e;var a=e.substr(0,e.indexOf("://")+3);this.pHrefType.value=a;if(this.pHrefType.value!=a)this.pHrefType.value="";this.pHrefExt.value=e.substring(e.indexOf("://")+3);this.firstFocus=this.pHrefExt}else{t.type="internal";this.pHrefIn.value=e||"";this.firstFocus=this.pHrefIn}}if(!t.type){if(t.text&&t.text.match(this.editor.autolinkEmailRegExp)){this.pHrefEmail.value=t.text;t.type="email";this.firstFocus=this.pHrefEmail}else{t.type="internal";this.pHrefIn.value=e||"";this.firstFocus=this.pHrefIn}}if(this.pType){this.pType.value=t.type}this.pInnerHtmlCont.style.display="none";this.pTextCont.style.display="none";if(t.bTextContent){this.pText.value=t.text||"";this.pTextCont.style.display=""}else{if(!t.text&&t.innerHtml){this.pInnerHtml.innerHTML=t.innerHtml;this.pInnerHtmlCont.style.display=""}else{this.pText.value=t.text||"";this.pTextCont.style.display=""}this._originalText=t.text}}if(!this.editor.bbCode){this.pTitle.value=t.title||"";this.pTarget.value=t.target||"_self";this.pClass.value=t.className||"";this.pId.value=t.id||"";this.pRel.value=t.rel||"";this.pNoindex.checked=t.noindex}this.ChangeType();if(!this.editor.bbCode){this.CheckShowStatParams();this.CheckNoindex();if(!this.oClass){this.oClass=new window.BXHtmlEditor.ClassSelector(this.editor,{id:this.id+"-class-selector",input:this.pClass,filterTag:"A",value:this.pClass.value});var l=this;BX.addCustomEvent(this.oClass,"OnComboPopupClose",function(){l.closeByEnter=true});BX.addCustomEvent(this.oClass,"OnComboPopupOpen",function(){l.closeByEnter=false})}else{this.oClass.OnChange()}}};_.prototype.GetValues=function(){var t=this.pType?this.pType.value:"internal",e={text:this.pText.value};if(!this.editor.bbCode){e.className="";e.title=this.pTitle.value;e.id=this.pId.value;e.rel=this.pRel.value;e.noindex=!!this.pNoindex.checked}if(t=="internal"){e.href=this.pHrefIn.value}else if(t=="external"){e.href=this.pHrefExt.value;if(this.pHrefType.value&&e.href.indexOf("://")==-1){e.href=this.pHrefType.value+e.href}if(this.pStat&&this.pStat.checked){e.href="/bitrix/redirect.php?event1="+escape(this.pStatEvent1.value)+"&event2="+escape(this.pStatEvent2.value)+"&event3="+escape(this.pStatEvent3.value)+"&goto="+escape(e.href)}}else if(t=="anchor"){e.href=this.pHrefAnchor.value}else if(t=="email"){e.href="mailto:"+this.pHrefEmail.value}if(this.pTarget&&this.pTarget.value!=="_self"){e.target=this.pTarget.value}if(this.pClass&&this.pClass.value){e.className=this.pClass.value}return e};_.prototype.Show=function(t,e){var i=this,s={},a,l,o,n,r=0;if(!this.readyToShow){return setTimeout(function(){i.Show(t,e)},100)}this.savedRange=e;if(!this.editor.bbCode||!this.editor.synchro.IsFocusedOnTextarea()){if(!t){t=this.editor.action.CheckState("formatInline",{},"a")}if(t){for(a=0;a<t.length;a++){o=t[a];if(o){n=o;r++}if(r>1){break}}if(r===1&&n&&n.querySelector){if(!n.querySelector("*")){s.text=this.editor.util.GetTextContent(n);s.bTextContent=true}else{s.text=this.editor.util.GetTextContent(n);if(BX.util.trim(s.text)==""){s.innerHtml=n.innerHTML}s.bTextContent=false}var h=n.getAttribute("data-bx-clean-attribute");if(h){n.removeAttribute(h);n.removeAttribute("data-bx-clean-attribute")}s.noindex=n.getAttribute("data-bx-noindex")=="Y";s.href=n.getAttribute("href");s.title=n.title;s.id=n.id;s.rel=n.getAttribute("rel");s.target=n.target;s.className=n.className}}else{var d=BX.util.trim(this.editor.selection.GetText());if(d&&d!=this.editor.INVISIBLE_SPACE){s.text=d}}this.bNewLink=t&&r>0;var p=[],c;if(document.querySelectorAll){var u=this.editor.sandbox.GetDocument().querySelectorAll(".bxhtmled-surrogate");l=u.length;for(a=0;a<l;a++){c=this.editor.GetBxTag(u[a]);if(c.tag=="anchor"){p.push({NAME:"#"+c.params.name,DESCRIPTION:BX.message("BXEdLinkHrefAnch")+": #"+c.params.name,CLASS_NAME:"bxhtmled-inp-popup-item"})}}}if(p.length>0){this.oHrefAnchor=new BXInputPopup({id:this.id+"-href-anchor-cntrl"+Math.round(Math.random()*1e9),values:p,input:this.pHrefAnchor,className:"bxhtmled-inp-popup"});BX.addCustomEvent(this.oHrefAnchor,"onInputPopupShow",function(t){if(t&&t.oPopup&&t.oPopup.popupContainer){t.oPopup.popupContainer.style.zIndex=3010}})}}else{s.text=this.editor.textareaView.GetTextSelection()}if(!this.editor.bbCode){window["OnFileDialogImgSelect"+this.editor.id]=window["OnFileDialogSelect"+this.editor.id]=function(t,e,s){var a;if(typeof t=="object"){a=t.src;if(i.pTitle)i.pTitle.value=t.description||t.name;if(i.pAlt)i.pAlt.value=t.description||t.name}else{a=(e=="/"?"":e)+"/"+t}i.pHrefIn.value=a;i.pHrefIn.focus();i.pHrefIn.select()}}this.SetValues(s);this.SetTitle(BX.message("InsertLink"));_.superclass.Show.apply(this,arguments)};function M(t,e){e={id:"bx_video",width:600,className:"bxhtmled-video-dialog"};this.sizes=[{key:"560x315",width:560,height:315},{key:"640x360",width:640,height:360},{key:"853x480",width:853,height:480},{key:"1280x720",width:1280,height:720}];M.superclass.constructor.apply(this,[t,e]);this.id="video_"+this.editor.id;this.waitCounter=false;this.SetContent(this.Build());BX.addCustomEvent(this,"OnDialogSave",BX.proxy(this.Save,this))}BX.extend(M,e);M.prototype.Build=function(){this.pCont=BX.create("DIV",{props:{className:"bxhtmled-video-dialog-cnt bxhtmled-video-cnt  bxhtmled-video-empty"}});var t=this,e,i,s=BX.create("TABLE",{props:{className:"bxhtmled-dialog-tbl bxhtmled-video-dialog-tbl"}});e=this.AddTableRow(s,{label:BX.message("BXEdVideoSource")+":",id:this.id+"-source"});this.pSource=e.rightCell.appendChild(BX.create("INPUT",{props:{id:this.id+"-source",type:"text",className:"bxhtmled-90-input",placeholder:BX.message("BXEdVideoSourcePlaceholder")}}));BX.bind(this.pSource,"change",BX.delegate(this.VideoSourceChanged,this));BX.bind(this.pSource,"mouseup",BX.delegate(this.VideoSourceChanged,this));BX.bind(this.pSource,"keyup",BX.delegate(this.VideoSourceChanged,this));e=s.insertRow(-1);i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-video-params-wrap"},attrs:{colSpan:2}});var a=i.appendChild(BX.create("TABLE",{props:{className:"bxhtmled-dialog-tbl bxhtmled-video-dialog-tbl"}}));e=this.AddTableRow(a,{label:BX.message("BXEdVideoInfoTitle")+":",id:this.id+"-title"});this.pTitle=e.rightCell.appendChild(BX.create("INPUT",{props:{id:this.id+"-title",type:"text",className:"bxhtmled-90-input",disabled:!!this.editor.bbCode}}));BX.addClass(e.row,"bxhtmled-video-ext-row bxhtmled-video-ext-loc-row");e=this.AddTableRow(a,{label:BX.message("BXEdVideoSize")+":",id:this.id+"-size"});this.pSize=e.rightCell.appendChild(BX.create("SELECT",{props:{id:this.id+"-size"}}));BX.addClass(e.row,"bxhtmled-video-ext-row");this.pUserSizeCnt=e.rightCell.appendChild(BX.create("SPAN",{props:{className:"bxhtmled-user-size"},style:{display:"none"}}));this.pUserSizeCnt.appendChild(BX.create("LABEL",{props:{className:"bxhtmled-width-lbl"},text:BX.message("BXEdImgWidth")+": ",attrs:{"for":this.id+"-width"}}));this.pWidth=this.pUserSizeCnt.appendChild(BX.create("INPUT",{props:{id:this.id+"-width",type:"text"}}));this.pUserSizeCnt.appendChild(BX.create("LABEL",{props:{className:"bxhtmled-width-lbl"},text:BX.message("BXEdImgHeight")+": ",attrs:{"for":this.id+"-height"}}));this.pHeight=this.pUserSizeCnt.appendChild(BX.create("INPUT",{props:{id:this.id+"-height",type:"text"}}));BX.bind(this.pSize,"change",function(){t.pUserSizeCnt.style.display=t.pSize.value==""?"":"none"});this.pPreviewCont=a.insertRow(-1);i=BX.adjust(this.pPreviewCont.insertCell(-1),{props:{title:BX.message("BXEdVideoPreview")},attrs:{colSpan:2}});this.pPreview=i.appendChild(BX.create("DIV",{props:{className:"bxhtmled-video-preview-cnt"}}));BX.addClass(this.pPreviewCont,"bxhtmled-video-ext-row bxhtmled-video-ext-loc-row");this.pCont.appendChild(s);return this.pCont};M.prototype.VideoSourceChanged=function(){var t=BX.util.trim(this.pSource.value);if(t!==this.lastSourceValue){this.lastSourceValue=t;if(this.editor.bbCode&&this.bEdit&&t.toLowerCase().indexOf("[/video]")!==-1)return;this.AnalyzeVideoSource(t)}};M.prototype.AnalyzeVideoSource=function(t){var e=this;if(t.match(/<iframe([\s\S]*?)\/iframe>/gi)){var i=this.editor.phpParser.CheckForVideo(t);if(i){var s=this.editor.phpParser.FetchVideoIframeParams(t,i.provider)||{};this.ShowVideoParams({html:t,provider:i.provider||false,title:s.origTitle||"",width:s.width||false,height:s.height||false})}}else{this.StartWaiting();this.editor.Request({getData:this.editor.GetReqData("video_oembed",{video_source:t}),handler:function(t){if(t.result){e.StopWaiting();e.ShowVideoParams(t.data)}else{e.StopWaiting();if(t.error!==""){e.ShowVideoParams(false)}}}})}};M.prototype.StartWaiting=function(){var t="",e=this;this.waitCounter=this.waitCounter===false||this.waitCounter>3?0:this.waitCounter;if(e.waitCounter==1)t=".";else if(e.waitCounter==2)t="..";else if(e.waitCounter==3)t="...";e.SetTitle(BX.message("BXEdVideoTitle")+t);this.StopWaiting(false);this.waitingTimeout=setTimeout(function(){e.waitCounter++;e.StartWaiting()},250)};M.prototype.StopWaiting=function(t){if(this.waitingTimeout){clearTimeout(this.waitingTimeout);this.waitingTimeout=null}if(t!==false){this.waitCounter=false;this.SetTitle(t||BX.message("BXEdVideoTitle"))}};M.prototype.ShowVideoParams=function(t){this.data=t||{};BX.removeClass(this.pCont,"bxhtmled-video-local");if(t===false||typeof t!="object"){BX.addClass(this.pCont,"bxhtmled-video-empty")}else if(t.local&&!this.bEdit){this.SetSize(400,300);BX.removeClass(this.pCont,"bxhtmled-video-empty");BX.addClass(this.pCont,"bxhtmled-video-local")}else{BX.removeClass(this.pCont,"bxhtmled-video-empty");if(t.provider){this.SetTitle(BX.message("BXEdVideoTitleProvider").replace("#PROVIDER_NAME#",BX.util.htmlspecialchars(t.provider)))}this.pTitle.value=t.title||"";this.SetSize(t.width,t.height);if(t.html){var e=Math.min(t.width,560),i=Math.min(t.height,315),s=t.html;s=this.UpdateHtml(s,e,i);this.pPreview.innerHTML=s;this.pPreviewCont.style.display=""}else{this.pPreviewCont.style.display="none"}}};M.prototype.SetSize=function(t,e){var i=t+"x"+e;if(!this.sizeIndex[i]){this.ClearSizeControl([{key:i,width:t,height:e,title:BX.message("BXEdVideoSizeAuto")+" ("+t+" x "+e+")"}].concat(this.sizes))}this.pSize.value=i};M.prototype.ClearSizeControl=function(t){t=t||this.sizes;this.pSize.options.length=0;this.sizeIndex={};for(var e=0;e<t.length;e++){this.sizeIndex[t[e].key]=true;this.pSize.options.add(new Option(t[e].title||t[e].width+" x "+t[e].height,t[e].key,false,false))}this.pSize.options.add(new Option(BX.message("BXEdVideoSizeCustom"),"",false,false))};M.prototype.UpdateHtml=function(t,e,i,s){var a=false;if(s){s=BX.util.htmlspecialchars(s)}t=t.replace(/((?:title)|(?:width)|(?:height))\s*=\s*("|')([\s\S]*?)(\2)/gi,function(t,l,o,n){l=l.toLowerCase();if(l=="width"&&e){return l+'="'+e+'"'}else if(l=="height"&&i){return l+'="'+i+'"'}else if(l=="title"&&s){a=true;return l+'="'+s+'"'}return""});if(!a&&s){t=t.replace(/<iframe\s*/i,function(t){return t+' title="'+s+'" '})}return t};M.prototype.Show=function(t,e){this.savedRange=e;if(this.savedRange){this.editor.selection.SetBookmark(this.savedRange)}this.SetTitle(BX.message("BXEdVideoTitle"));this.ClearSizeControl();this.bEdit=t&&t.tag=="video";this.bxTag=t;if(this.bEdit){this.pSource.value=this.lastSourceValue=t.params.value;if(!this.editor.bbCode)this.AnalyzeVideoSource(t.params.value)}else{this.ShowVideoParams(false);this.pSource.value=""}M.superclass.Show.apply(this,arguments)};M.prototype.Save=function(){var t=this,e=this.pTitle.value,i=parseInt(this.pWidth.value)||100,s=parseInt(this.pHeight.value)||100;if(this.pSize.value!==""){var a=this.pSize.value.split("x");if(a&&a.length==2){i=parseInt(a[0]);s=parseInt(a[1])}}if(this.data&&this.data.html)this.data.html=this.UpdateHtml(this.data.html,i,s,e);var l="",o="";if(this.bEdit){if(this.bxTag&&this.editor.bbCode&&!this.data){this.bxTag.params.value=this.pSource.value}else if(this.data&&this.editor.action.IsSupported("insertHTML")){var n=this.editor.GetIframeElement(this.bxTag.id);if(n){this.editor.selection.SelectNode(n);BX.remove(n)}o=this.data.html}}else if(this.data){if(this.editor.bbCode&&this.data.local){l=this.data.html="[VIDEO width="+i+" height="+s+"]"+this.data.path+"[/VIDEO]";o=this.editor.bbParser.GetVideoSourse(this.data.path,{type:false,width:i,height:s,html:this.data.html},this.data.html)}else if(this.data.html){if(this.savedRange){this.editor.selection.SetBookmark(this.savedRange)}if(t.editor.synchro.IsFocusedOnTextarea()){var r=this.editor.phpParser.FetchVideoIframeParams(this.data.html);l="[VIDEO TYPE="+this.data.provider.toUpperCase()+" WIDTH="+this.data.width+" HEIGHT="+this.data.height+"]"+r.src+"[/VIDEO]"}o=this.data.html}}if(t.editor.synchro.IsFocusedOnTextarea()){if(l!=="")this.editor.textareaView.WrapWith(false,false,l);t.editor.synchro.Sync()}else{if(o!==""&&this.editor.action.IsSupported("insertHTML")){this.editor.action.Exec("insertHTML",o)}setTimeout(function(){t.editor.synchro.FullSyncFromIframe()},50)}};function G(t,e){e={id:"bx_source",height:400,width:700,resizable:true,className:"bxhtmled-source-dialog"};G.superclass.constructor.apply(this,[t,e]);this.id="source_"+this.editor.id;this.SetContent(this.Build());BX.addCustomEvent(this,"OnDialogSave",BX.proxy(this.Save,this))}BX.extend(G,e);G.prototype.Build=function(){this.pValue=BX.create("TEXTAREA",{props:{className:"bxhtmled-source-value",id:this.id+"-value"}});return this.pValue};G.prototype.OnResize=function(){var t=this.oDialog.PARTS.CONTENT_DATA.offsetWidth,e=this.oDialog.PARTS.CONTENT_DATA.offsetHeight;this.pValue.style.width=t-30+"px";this.pValue.style.height=e-30+"px"};G.prototype.OnResizeFinished=function(){};G.prototype.Save=function(){this.bxTag.params.value=this.pValue.value;this.editor.SetBxTag(false,this.bxTag);var t=this;setTimeout(function(){t.editor.synchro.FullSyncFromIframe()},50)};G.prototype.Show=function(t){this.bxTag=t;if(t&&t.tag){this.SetTitle(t.name);this.pValue.value=t.params.value;G.superclass.Show.apply(this,arguments);this.OnResize();BX.focus(this.pValue)}};function W(t,e){e={id:"bx_anchor",width:300,resizable:false,className:"bxhtmled-anchor-dialog"};W.superclass.constructor.apply(this,[t,e]);this.id="anchor_"+this.editor.id;this.SetContent(this.Build());BX.addCustomEvent(this,"OnDialogSave",BX.proxy(this.Save,this))}BX.extend(W,e);W.prototype.Build=function(){var t=BX.create("DIV");t.appendChild(BX.create("LABEL",{text:BX.message("BXEdAnchorName")+": "})).setAttribute("for",this.id+"-value");this.pValue=t.appendChild(BX.create("INPUT",{props:{className:"",id:this.id+"-value"}}));return t};W.prototype.Save=function(){this.bxTag.params.name=BX.util.trim(this.pValue.value.replace(/[^ a-z0-9_\-]/gi,""));this.editor.SetBxTag(false,this.bxTag);var t=this;setTimeout(function(){t.editor.synchro.FullSyncFromIframe()},50)};W.prototype.Show=function(t){this.bxTag=t;if(t&&t.tag){this.SetTitle(BX.message("BXEdAnchor"));this.pValue.value=t.params.name;W.superclass.Show.apply(this,arguments);BX.focus(this.pValue);this.pValue.select()}};function U(t,e){e={id:"bx_table",width:t.bbCode?300:600,resizable:false,className:"bxhtmled-table-dialog"};_.superclass.constructor.apply(this,[t,e]);this.id="table"+this.editor.id;this.action="insertTable";this.SetContent(this.Build());BX.addCustomEvent(this,"OnDialogSave",BX.proxy(this.Save,this))}BX.extend(U,e);U.prototype.Build=function(){
var t,e,i,s=BX.create("DIV");var a=BX.create("TABLE",{props:{className:"bxhtmled-dialog-tbl bxhtmled-dialog-tbl-hide-additional"}});e=a.insertRow(-1);i=BX.adjust(e.insertCell(-1),{attrs:{colSpan:4}});t=i.appendChild(BX.create("TABLE",{props:{className:"bxhtmled-dialog-tbl"}}));e=t.insertRow(-1);i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-lbl-cell"}});i.appendChild(BX.create("LABEL",{text:BX.message("BXEdTableRows")+":",attrs:{"for":this.id+"-rows"}}));i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-val-cell"}});this.pRows=i.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-rows"}}));if(!this.editor.bbCode){i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-lbl-cell"}});i.appendChild(BX.create("LABEL",{text:BX.message("BXEdTableWidth")+":",attrs:{"for":this.id+"-width"}}));i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-val-cell"}});this.pWidth=i.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-width"}}))}e=t.insertRow(-1);i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-lbl-cell"}});i.appendChild(BX.create("LABEL",{text:BX.message("BXEdTableCols")+":",attrs:{"for":this.id+"-cols"}}));i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-val-cell"}});this.pCols=i.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-cols"}}));if(!this.editor.bbCode){i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-lbl-cell"}});i.appendChild(BX.create("LABEL",{text:BX.message("BXEdTableHeight")+":",attrs:{"for":this.id+"-height"}}));i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-val-cell"}});this.pHeight=i.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-height"}}))}if(!this.editor.bbCode){e=a.insertRow(-1);var l=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-title-cell bxhtmled-title-cell-foldable",colSpan:4},text:BX.message("BXEdLinkAdditionalTitle")});BX.bind(l,"click",function(){BX.toggleClass(a,"bxhtmled-dialog-tbl-hide-additional")});var o=a.appendChild(BX.create("TBODY",{props:{className:"bxhtmled-additional-tbody"}}));e=o.insertRow(-1);i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-lbl-cell"}});i.appendChild(BX.create("LABEL",{text:BX.message("BXEdTableHeads")+":",attrs:{"for":this.id+"-th"}}));i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-val-cell"}});this.pHeaders=i.appendChild(BX.create("SELECT",{props:{id:this.id+"-th"},style:{width:"130px"}}));this.pHeaders.options.add(new Option(BX.message("BXEdThNone"),"",true,true));this.pHeaders.options.add(new Option(BX.message("BXEdThTop"),"top",false,false));this.pHeaders.options.add(new Option(BX.message("BXEdThLeft"),"left",false,false));this.pHeaders.options.add(BX.adjust(new Option(BX.message("BXEdThTopLeft"),"topleft",false,false),{props:{title:BX.message("BXEdThTopLeftTitle")}}));i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-lbl-cell"}});i.appendChild(BX.create("LABEL",{text:BX.message("BXEdTableCellSpacing")+":",attrs:{"for":this.id+"-cell-spacing"}}));i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-val-cell"}});this.pCellSpacing=i.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-cell-spacing"}}));e=o.insertRow(-1);i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-lbl-cell"}});i.appendChild(BX.create("LABEL",{text:BX.message("BXEdTableBorder")+":",attrs:{"for":this.id+"-border"}}));i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-val-cell"}});this.pBorder=i.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-border"}}));i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-lbl-cell"}});i.appendChild(BX.create("LABEL",{text:BX.message("BXEdTableCellPadding")+":",attrs:{"for":this.id+"-cell-padding"}}));i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-val-cell"}});this.pCellPadding=i.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-cell-padding"}}));e=o.insertRow(-1);i=BX.adjust(e.insertCell(-1),{attrs:{colSpan:4}});t=i.appendChild(BX.create("TABLE",{props:{className:"bxhtmled-dialog-inner-tbl"}}));e=t.insertRow(-1);i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-inner-lbl-cell"}});i.appendChild(BX.create("LABEL",{text:BX.message("BXEdTableAlign")+":",attrs:{"for":this.id+"-align"}}));i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-inner-val-cell"}});this.pAlign=i.appendChild(BX.create("SELECT",{props:{id:this.id+"-align"},style:{width:"130px"}}));this.pAlign.options.add(new Option(BX.message("BXEdTableAlignLeft"),"left",true,true));this.pAlign.options.add(new Option(BX.message("BXEdTableAlignCenter"),"center",false,false));this.pAlign.options.add(new Option(BX.message("BXEdTableAlignRight"),"right",false,false));e=t.insertRow(-1);i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-inner-lbl-cell"}});i.appendChild(BX.create("LABEL",{text:BX.message("BXEdTableCaption")+":",attrs:{"for":this.id+"-caption"}}));i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-inner-val-cell"}});this.pCaption=i.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-caption"}}));e=t.insertRow(-1);i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-inner-lbl-cell"}});i.appendChild(BX.create("LABEL",{text:BX.message("BXEdCssClass")+":",attrs:{"for":this.id+"-class"}}));i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-inner-val-cell"}});this.pClass=i.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-class"}}));e=t.insertRow(-1);i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-inner-lbl-cell"}});i.appendChild(BX.create("LABEL",{text:BX.message("BXEdTableId")+":",attrs:{"for":this.id+"-id"}}));i=BX.adjust(e.insertCell(-1),{props:{className:"bxhtmled-inner-val-cell"}});this.pId=i.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-id"}}))}s.appendChild(a);return s};U.prototype.SetValues=function(t){this.pRows.value=t.rows||3;this.pCols.value=t.cols||4;if(!this.editor.bbCode){this.pWidth.value=t.width||"";this.pHeight.value=t.height||"";this.pId.value=t.id||"";this.pCaption.value=t.caption||"";this.pCellPadding.value=t.cellPadding||0;this.pCellSpacing.value=t.cellSpacing||0;this.pBorder.value=t.border||"";this.pClass.value=t.className||"";this.pHeaders.value=t.headers||"";this.pRows.disabled=this.pCols.disabled=!!this.currentTable;this.pAlign.value=t.align||"left";if(!this.oClass){this.oClass=new window.BXHtmlEditor.ClassSelector(this.editor,{id:this.id+"-class-selector",input:this.pClass,filterTag:"TABLE",value:this.pClass.value});var e=this;BX.addCustomEvent(this.oClass,"OnComboPopupClose",function(){e.closeByEnter=true});BX.addCustomEvent(this.oClass,"OnComboPopupOpen",function(){e.closeByEnter=false})}else{this.oClass.OnChange()}}};U.prototype.GetValues=function(){var t={table:this.currentTable||false,rows:parseInt(this.pRows.value)||1,cols:parseInt(this.pCols.value)||1};if(!this.editor.bbCode){t.width=BX.util.trim(this.pWidth.value);t.height=BX.util.trim(this.pHeight.value);t.id=BX.util.trim(this.pId.value);t.caption=BX.util.trim(this.pCaption.value);t.cellPadding=parseInt(this.pCellPadding.value)||"";t.cellSpacing=parseInt(this.pCellSpacing.value)||"";t.border=parseInt(this.pBorder.value)||"";t.headers=this.pHeaders.value;t.className=this.pClass.value;t.align=this.pAlign.value}return t};U.prototype.Show=function(t,e){var i,s={};this.savedRange=e;if(this.savedRange){this.editor.selection.SetBookmark(this.savedRange)}if(!t){t=this.editor.action.CheckState("insertTable")}if(t&&t.nodeName){i=t}else if(t&&t[0]&&t[0].nodeName){i=t[0]}this.currentTable=false;if(i){this.currentTable=i;s.rows=i.rows.length;s.cols=i.rows[0].cells.length;if(i.style.width){s.width=i.style.width}if(!s.width&&i.width){s.width=i.width}if(i.style.height){s.height=i.style.height}if(!s.height&&i.height){s.height=i.height}s.cellPadding=i.getAttribute("cellPadding")||"";s.cellSpacing=i.getAttribute("cellSpacing")||"";s.border=i.getAttribute("border")||0;s.id=i.getAttribute("id")||"";var a=BX.findChild(i,{tag:"CAPTION"},false);s.caption=a?BX.util.htmlspecialcharsback(a.innerHTML):"";s.className=i.className||"";var l,o,n,r=true,h=true;for(l=0;l<i.rows.length;l++){for(o=0;o<i.rows[l].cells.length;o++){n=i.rows[l].cells[o];if(l==0){r=n.nodeName=="TH"&&r}if(o==0){h=n.nodeName=="TH"&&h}}}if(!r&&!h){s.headers=""}else if(r&&h){s.headers="topleft"}else if(r){s.headers="top"}else{s.headers="left"}s.align=i.getAttribute("align")}this.SetValues(s);this.SetTitle(BX.message("BXEdTable"));U.superclass.Show.apply(this,arguments)};function j(t,e){e={id:"bx_settings",width:400,resizable:false};this.id="settings";q.superclass.constructor.apply(this,[t,e]);this.SetContent(this.Build());BX.addCustomEvent(this,"OnDialogSave",BX.proxy(this.Save,this))}BX.extend(j,e);j.prototype.Build=function(){this.pCont=BX.create("DIV",{props:{className:"bxhtmled-settings-dialog-cnt"}});var t=this,e,i,s=BX.create("TABLE",{props:{className:"bxhtmled-dialog-tbl"}});e=this.AddTableRow(s);this.pCleanSpans=e.leftCell.appendChild(BX.create("INPUT",{props:{id:this.id+"-clean-spans",type:"checkbox"}}));e.rightCell.appendChild(BX.create("LABEL",{html:BX.message("BXEdSettingsCleanSpans")})).setAttribute("for",this.id+"-clean-spans");e=s.insertRow(-1);i=e.insertCell(-1);BX.adjust(i,{props:{className:"bxhtmled-title-cell",colSpan:2},text:BX.message("BXEdPasteSettings")});e=this.AddTableRow(s);this.pPasteSetColors=e.leftCell.appendChild(BX.create("INPUT",{props:{id:this.id+"-ps-colors",type:"checkbox"}}));e.rightCell.appendChild(BX.create("LABEL",{html:BX.message("BXEdPasteSetColors")})).setAttribute("for",this.id+"-ps-colors");e=this.AddTableRow(s);this.pPasteSetBgBorders=e.leftCell.appendChild(BX.create("INPUT",{props:{id:this.id+"-ps-border",type:"checkbox"}}));e.rightCell.appendChild(BX.create("LABEL",{html:BX.message("BXEdPasteSetBgBorders")})).setAttribute("for",this.id+"-ps-border");e=this.AddTableRow(s);this.pPasteSetDecor=e.leftCell.appendChild(BX.create("INPUT",{props:{id:this.id+"-ps-decor",type:"checkbox"}}));e.rightCell.appendChild(BX.create("LABEL",{html:BX.message("BXEdPasteSetDecor")})).setAttribute("for",this.id+"-ps-decor");this.pCont.appendChild(s);return this.pCont};j.prototype.Show=function(){var t={};this.SetValues(t);this.SetTitle(BX.message("BXEdSettings"));this.pCleanSpans.checked=this.editor.config.cleanEmptySpans;this.pPasteSetColors.checked=this.editor.config.pasteSetColors;this.pPasteSetBgBorders.checked=this.editor.config.pasteSetBorders;this.pPasteSetDecor.checked=this.editor.config.pasteSetDecor;j.superclass.Show.apply(this,arguments)};j.prototype.Save=function(){this.editor.config.cleanEmptySpans=this.pCleanSpans.checked;this.editor.config.pasteSetColors=this.pPasteSetColors.checked;this.editor.config.pasteSetBorders=this.pPasteSetBgBorders.checked;this.editor.config.pasteSetDecor=this.pPasteSetDecor.checked;this.editor.SaveOption("clean_empty_spans",this.editor.config.cleanEmptySpans?"Y":"N");this.editor.SaveOption("paste_clear_colors",this.editor.config.pasteSetColors?"Y":"N");this.editor.SaveOption("paste_clear_borders",this.editor.config.pasteSetBorders?"Y":"N");this.editor.SaveOption("paste_clear_decor",this.editor.config.pasteSetDecor?"Y":"N")};function q(t,e){e={id:"bx_default",width:500,resizable:false,className:"bxhtmled-default-dialog"};this.id="default";this.action="universalFormatStyle";q.superclass.constructor.apply(this,[t,e]);this.SetContent(this.Build());BX.addCustomEvent(this,"OnDialogSave",BX.proxy(this.Save,this))}BX.extend(q,e);q.prototype.Show=function(t,e){var i=[],s,a,l=typeof t!=="object"||t.length==0,o;this.savedRange=e;if(this.savedRange){this.editor.selection.SetBookmark(this.savedRange)}if(l){t=this.editor.action.CheckState(this.action)}if(!t){t=[]}if(t.length==1&&BX.util.in_array(t[0].nodeName,["TD","TH","TR","TABLE"])){this.colorRow.style.display=""}else{this.colorRow.style.display="none"}for(s=0;s<t.length;s++){if(a===undefined&&o===undefined){a=t[s].style.cssText;o=t[s].className}else{a=t[s].style.cssText===a?a:false;o=t[s].className===o?o:false}i.push(t[s].nodeName)}this.SetValues({nodes:t,renewNodes:l,style:a,className:o});this.SetTitle(BX.message("BXEdDefaultPropDialog").replace("#NODES_LIST#",i.join(", ")));q.superclass.Show.apply(this,arguments)};q.prototype.Build=function(){var t,e,i=this,s=BX.create("DIV");var a=BX.create("TABLE",{props:{className:"bxhtmled-dialog-tbl"}});t=a.insertRow(-1);e=BX.adjust(t.insertCell(-1),{props:{className:"bxhtmled-left-c"}});e.appendChild(BX.create("LABEL",{text:BX.message("BXEdCssClass")+":",attrs:{"for":this.id+"-css-class"}}));e=BX.adjust(t.insertCell(-1),{props:{className:"bxhtmled-right-c"}});this.pCssClass=e.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-css-class"}}));t=a.insertRow(-1);e=BX.adjust(t.insertCell(-1),{props:{className:"bxhtmled-left-c"}});e.appendChild(BX.create("LABEL",{text:BX.message("BXEdCSSStyle")+":",attrs:{"for":this.id+"-css-style"}}));e=BX.adjust(t.insertCell(-1),{props:{className:"bxhtmled-right-c"}});this.pCssStyle=e.appendChild(BX.create("INPUT",{props:{type:"text",id:this.id+"-css-style"}}));t=a.insertRow(-1);e=BX.adjust(t.insertCell(-1),{props:{className:"bxhtmled-left-c"}});e.appendChild(BX.create("LABEL",{text:BX.message("BXEdColorpickerDialog")+":",attrs:{"for":this.id+"-css-class"}}));e=BX.adjust(t.insertCell(-1),{props:{className:"bxhtmled-right-c"}});this.textColor=e.appendChild(BX.create("INPUT",{props:{type:"hidden",id:this.id+"-s"}}));this.bgColor=e.appendChild(BX.create("INPUT",{props:{type:"hidden",id:this.id+"-s"}}));var l=new window.BXHtmlEditor.Controls.Color(this.editor,e,{registerActions:false,checkAction:false,BgColorMess:BX.message("BXEdBgColor"),callback:function(t,e){var s,a=" "+i.pCssStyle.value;if(t=="foreColor"){s=a.replace(/\s+color\s*:\s*([\s\S]*?);/gi,e?" color: "+e+";":"");if(s.toLowerCase()!=a.toLowerCase())a=s;else if(e)a+=" color: "+e+";"}else if(t=="backgroundColor"){s=a.replace(/background-color\s*:\s*([\s\S]*?);/gi,"background-color: "+e+";");if(s.toLowerCase()!=a.toLowerCase())a=s;else if(e)a+=" background-color: "+e+";"}i.pCssStyle.value=BX.util.trim(a)}});this.colorRow=t;s.appendChild(a);return s};q.prototype.SetValues=function(t){if(!t){t={}}this.nodes=t.nodes||[];this.renewNodes=t.renewNodes;this.pCssStyle.value=this.editor.util.RgbToHex(t.style||"");this.pCssClass.value=t.className||"";if(!this.oClass){this.oClass=new window.BXHtmlEditor.ClassSelector(this.editor,{id:this.id+"-class-selector",input:this.pCssClass,filterTag:"A",value:this.pCssClass.value});var e=this;BX.addCustomEvent(this.oClass,"OnComboPopupClose",function(){e.closeByEnter=true});BX.addCustomEvent(this.oClass,"OnComboPopupOpen",function(){e.closeByEnter=false})}else{this.oClass.OnChange()}};q.prototype.GetValues=function(){return{className:this.pCssClass.value,style:this.pCssStyle.value,nodes:this.renewNodes?[]:this.nodes}};function Y(t,e){this.editor=t;e={id:"bx_char",width:570,resizable:false,className:"bxhtmled-char-dialog"};this.id="char"+this.editor.id;Y.superclass.constructor.apply(this,[t,e]);this.oDialog.ClearButtons();this.oDialog.SetButtons([this.oDialog.btnCancel]);this.SetContent(this.Build());BX.addCustomEvent(this,"OnDialogSave",BX.proxy(this.Save,this))}BX.extend(Y,e);Y.prototype.Build=function(){var t=this,e,i,s,a=18,l,o=this.editor.HTML_ENTITIES.length,n=BX.create("DIV");var r=BX.create("TABLE",{props:{className:"bxhtmled-specialchar-tbl"}});for(s=0;s<o;s++){if(s%a==0){e=r.insertRow(-1)}l=this.editor.HTML_ENTITIES[s];i=e.insertCell(-1);i.innerHTML=l;i.setAttribute("data-bx-specialchar",l);i.title=BX.message("BXEdSpecialchar")+": "+l.substr(1,l.length-2)}BX.bind(r,"click",function(e){var i,s=e.target||e.srcElement;if(s.nodeType==3){s=s.parentNode}if(s&&s.getAttribute&&s.getAttribute("data-bx-specialchar")&&t.editor.action.IsSupported("insertHTML")){if(t.savedRange){t.editor.selection.SetBookmark(t.savedRange)}i=s.getAttribute("data-bx-specialchar");t.editor.On("OnSpecialcharInserted",[i]);t.editor.action.Exec("insertHTML",i)}t.oDialog.Close()});n.appendChild(r);return n};Y.prototype.SetValues=BX.DoNothing;Y.prototype.GetValues=BX.DoNothing;Y.prototype.Show=function(t){this.savedRange=t;if(this.savedRange){this.editor.selection.SetBookmark(this.savedRange)}this.SetTitle(BX.message("BXEdSpecialchar"));Y.superclass.Show.apply(this,arguments)};function K(t,e){this.editor=t;e={id:"bx_list",width:360,resizable:false,className:"bxhtmled-list-dialog"};this.id="list"+this.editor.id;K.superclass.constructor.apply(this,[t,e]);this.SetContent(this.Build());BX.addCustomEvent(this,"OnDialogSave",BX.proxy(this.Save,this))}BX.extend(K,e);K.prototype.Build=function(){var t=this,e,i,s,a=18,l,o=this.editor.HTML_ENTITIES.length,n=BX.create("DIV");this.itemsWrap=n.appendChild(BX.create("DIV",{props:{className:"bxhtmled-list-wrap"}}));this.addItem=n.appendChild(BX.create("span",{props:{className:"bxhtmled-list-add-item"},text:BX.message("BXEdAddListItem")}));BX.bind(this.addItem,"click",BX.proxy(this.AddItem,this));return n};K.prototype.BuildList=function(t){if(this.pList){BX.remove(this.pList)}this.pList=this.itemsWrap.appendChild(BX.create(t,{props:{className:"bxhtmled-list"}}));this.AddItem({focus:true});this.AddItem({focus:false});this.AddItem({focus:false})};K.prototype.AddItem=function(t){if(typeof t!=="object")t={};var e=BX.create("LI"),i=e.appendChild(BX.create("INPUT",{props:{type:"text",value:"",size:35}}));this.pList.appendChild(e);var s=e.appendChild(BX.create("SPAN",{props:{className:"bxhtmled-list-del-item",title:BX.message("DelListItem")}}));if(t.focus!==false){setTimeout(function(){BX.focus(i)},100)}BX.bind(i,"keyup",BX.proxy(this.CheckList,this));BX.bind(i,"mouseup",BX.proxy(this.CheckList,this));BX.bind(i,"focus",BX.proxy(this.CheckList,this));BX.bind(s,"click",BX.proxy(this.RemoveItem,this))};K.prototype.RemoveItem=function(t){var e=t.target||t.srcElement,i=BX.findParent(e,{tag:"LI"});if(i){BX.remove(i)}};K.prototype.CheckList=function(){var t=this.pList.getElementsByTagName("LI");if(t.length<3){this.AddItem({focus:false});this.CheckList({focus:false})}else{if(t[t.length-1].firstChild&&t[t.length-1].firstChild.value!==""){this.AddItem({focus:false})}}};K.prototype.InputKeyNavigation=function(t){var e=t.target||t.srcElement,i=t.keyCode};K.prototype.SetValues=BX.DoNothing;K.prototype.GetValues=BX.DoNothing;K.prototype.Show=function(t){this.type=t.type;this.SetTitle(t.type=="ul"?BX.message("UnorderedList"):BX.message("OrderedList"));this.BuildList(t.type);K.superclass.Show.apply(this,arguments)};K.prototype.Save=function(){var t,e=[],i=this.pList.getElementsByTagName("INPUT");for(t=0;t<i.length;t++){if(i[t].value!==""){e.push(i[t].value)}}this.editor.action.Exec(this.type=="ul"?"insertUnorderedList":"insertOrderedList",{items:e})};window.BXHtmlEditor.Controls={SearchButton:s,ChangeView:a,Undo:o,Redo:n,StyleSelector:r,FontSelector:h,FontSize:d,Bold:p,Italic:c,Underline:u,Strikeout:m,Color:i,RemoveFormat:f,TemplateSelector:C,OrderedList:b,UnorderedList:g,IndentButton:B,OutdentButton:X,AlignList:v,InsertLink:y,InsertImage:x,InsertVideo:S,InsertAnchor:w,InsertTable:N,InsertChar:I,Settings:P,Fullscreen:V,PrintBreak:E,PageBreak:T,InsertHr:k,Spellcheck:A,Code:R,Quote:D,Smile:H,Sub:L,Sup:O,More:F,BbCode:l};window.BXHtmlEditor.dialogs.Image=z;window.BXHtmlEditor.dialogs.Link=_;window.BXHtmlEditor.dialogs.Video=M;window.BXHtmlEditor.dialogs.Source=G;window.BXHtmlEditor.dialogs.Anchor=W;window.BXHtmlEditor.dialogs.Table=U;window.BXHtmlEditor.dialogs.Settings=j;window.BXHtmlEditor.dialogs.Default=q;window.BXHtmlEditor.dialogs.Specialchar=Y;window.BXHtmlEditor.dialogs.InsertList=K}if(window.BXHtmlEditor&&window.BXHtmlEditor.Button&&window.BXHtmlEditor.Dialog)t();else BX.addCustomEvent(window,"OnEditorBaseControlsDefined",t)})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:65:"/bitrix/js/fileman/html_editor/html-components.js?145227744812842";s:6:"source";s:49:"/bitrix/js/fileman/html_editor/html-components.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
/**
 * Bitrix HTML Editor 3.0
 * Date: 24.04.13
 * Time: 4:23
 *
 * Components class
 */
(function()
{
	function BXEditorComponents(editor)
	{
		this.editor = editor;
		this.phpParser = this.editor.phpParser;
		this.listLoaded = false;
		this.components = this.editor.config.components;
		this.compNameIndex = {};
		this.componentIncludeMethod = '$APPLICATION->IncludeComponent';

		this.requestUrl = '/bitrix/admin/fileman_component_params.php';
		this.HandleList();

		this.Init();
	}

	BXEditorComponents.prototype = {
		Init: function()
		{
			BX.addCustomEvent(this.editor, "OnSurrogateDblClick", BX.proxy(this.OnComponentDoubleClick, this));
		},

		GetList: function()
		{
			return this.components;
		},

		HandleList: function()
		{
			if (this.components && this.components.items)
			{
				for(var i = 0; i < this.components.items.length; i++)
					this.compNameIndex[this.components.items[i].name] = i;
			}
		},

		IsComponent: function(code)
		{
			code = this.phpParser.TrimPhpBrackets(code);
			code = this.phpParser.CleanCode(code);

			var oFunction = this.phpParser.ParseFunction(code);
			if (oFunction && oFunction.name.toUpperCase() == this.componentIncludeMethod.toUpperCase())
			{
				var arParams = this.phpParser.ParseParameters(oFunction.params);
				return {
					name: arParams[0],
					template: arParams[1] || "",
					params: arParams[2] || {},
					parentComponent: (arParams[3] && arParams[3] != '={false}') ? arParams[3] : false,
					exParams: arParams[4] || false
				};
			}
			return false;
		},

		IsReady: function()
		{
			return this.listLoaded;
		},

		GetSource: function(params)
		{
			if (!this.arVA)
			{
				this.arVA = {};
			}

			var
				res = "<?" + this.componentIncludeMethod + "(\n" +
					"\t\"" + params.name+"\",\n" +
					"\t\"" + (params.template || "") + "\",\n";

			if (params.params)
			{
				res += "\tArray(\n";

				var
					propValues = params.params,
					i, k, cnt,
					_len1 = "SEF_URL_TEMPLATES_".length,
					_len2 = "VARIABLE_ALIASES_".length,
					SUT, VA, lio, templ_key,
					params_exist = false,
					arVal, arLen, j;

				for (i in propValues)
				{
					if (!propValues.hasOwnProperty(i))
						continue;

					//try{
						if (!params_exist)
							params_exist = true;

						if (typeof(propValues[i]) == 'string')
						{
							propValues[i] = this.editor.util.stripslashes(propValues[i]);
						}
						else if (typeof(propValues[i]) == 'object')
						{
							arVal = 'array(';
							arLen = 0;
							for (j in propValues[i])
							{
								if (propValues[i].hasOwnProperty(j) && typeof(propValues[i][j]) == 'string')
								{
									arLen++;
									arVal += '"' + this.editor.util.stripslashes(propValues[i][j]) + '",';
								}
							}
							if (arLen > 0)
								arVal = arVal.substr(0, arVal.length - 1) + ')';
							else
								arVal += ')';

							propValues[i] = arVal;
						}
						else
						{
							continue;
						}

						if (propValues["SEF_MODE"] && propValues["SEF_MODE"].toUpperCase() == "Y")
						{
							//*** Handling SEF_URL_TEMPLATES in SEF = ON***
							if(i.substr(0, _len1) == "SEF_URL_TEMPLATES_")
							{
								templ_key = i.substr(_len1);
								this.arVA[templ_key] = this.CatchVariableAliases(propValues[i]);

								if (!SUT)
								{
									res += "\t\t\"" + i.substr(0, _len1 - 1) + "\" => Array(\n"
									SUT = true;
								}
								res += "\t\t\t\"" + i.substr(_len1) + "\" => ";
								if (this.IsPHPBracket(propValues[i]))
									res += this.TrimPHPBracket(propValues[i]);
								else
									res += "\"" + this.editor.util.addslashes(propValues[i])+"\"";

								res += ",\n";
								continue;
							}
							else if (SUT)
							{
								lio = res.lastIndexOf(",");
								res = res.substr(0,lio)+res.substr(lio+1);
								SUT = false;
								res += "\t\t),\n";
							}

							//*** Handling  VARIABLE_ALIASES  in SEF = ON***
							if(i.substr(0, _len2) == "VARIABLE_ALIASES_")
								continue;
						}
						else if(propValues["SEF_MODE"] == "N")
						{
							//*** Handling SEF_URL_TEMPLATES in SEF = OFF ***
							if (i.substr(0, _len1)=="SEF_URL_TEMPLATES_" || i == "SEF_FOLDER")
								continue;

							//*** Handling VARIABLE_ALIASES  in SEF = OFF ***
							if(i.substr(0, _len2) == "VARIABLE_ALIASES_")
							{
								if (!VA)
								{
									res += "\t\t\"" + i.substr(0, _len2 - 1) + "\" => Array(\n";
									VA = true;
								}
								res += "\t\t\t\"" + i.substr(_len2) + "\" => \"" + this.editor.util.addslashes(propValues[i]) + "\",\n";
								continue;
							}
							else if (VA)
							{
								lio = res.lastIndexOf(",");
								res = res.substr(0, lio) + res.substr(lio + 1);
								VA = false;
								res += "\t\t),\n";
							}
						}

						res += "\t\t\"" + i + "\" => ";
						if (this.IsPHPBracket(propValues[i]))
							res += this.TrimPHPBracket(propValues[i]);
						else if (propValues[i].substr(0, 6).toLowerCase() == 'array(')
							res += propValues[i];
						else
							res += '"' + this.editor.util.addslashes(propValues[i]) + '"';
						res += ",\n";

					//}catch(e){continue;}
				}

				if (VA || SUT)
				{
					lio = res.lastIndexOf(",");
					res = res.substr(0, lio) + res.substr(lio+1);
					VA = false;
					SUT = false;
					res += "\t\t),\n";
				}

				if (propValues["SEF_MODE"] && propValues["SEF_MODE"].toUpperCase() == "Y")
				{
					res += "\t\t\"VARIABLE_ALIASES\" => Array(\n";

					if (this.arVA)
					{
						for (templ_key in this.arVA)
						{
							if (!this.arVA.hasOwnProperty(templ_key) || typeof(this.arVA[templ_key]) != 'object')
								continue;
							res += "\t\t\t\""+templ_key+"\" => Array(";

							cnt = 0;
							for (k in this.arVA[templ_key])
							{
								if (!this.arVA[templ_key].hasOwnProperty(k) || typeof(this.arVA[templ_key][k]) != 'string')
									continue;
								cnt++;
								res += "\n\t\t\t\t\"" + k +"\" => \"" + this.arVA[templ_key][k]+"\",";
							}

							if (cnt > 0)
							{
								lio = res.lastIndexOf(",");
								res = res.substr(0, lio) + res.substr(lio + 1);
								res += "\n\t\t\t),\n";
							}
							else
							{
								res += "),\n";
							}
						}
					}

					res += "\t\t),\n";
				}

				if (params_exist)
				{
					lio = res.lastIndexOf(",");
					res = res.substr(0, lio) + res.substr(lio + 1);
				}
				res += "\t)";
			}
			else
			{
				res += "Array()"
			}

			if (params.parentComponent !== false || params.exParams !== false)
			{
				var pc = params.parentComponent;
				if (!pc || pc.toLowerCase() == '={false}')
				{
					res += ",\nfalse";
				}
				else
				{
					if (this.IsPHPBracket(pc))
						res += ",\n" + this.TrimPHPBracket(pc);
					else
						res += ",\n'" + pc + "'";
				}

				if (params.exParams !== false && typeof params.exParams == 'object')
				{
					res += ",\nArray(";
					for (i in params.exParams)
					{
						if (params.exParams.hasOwnProperty(i) && typeof(params.exParams[i]) == 'string')
						{
							res += "\n\t'" + i + "' => '" + this.editor.util.stripslashes(params.exParams[i]) + "',";
						}
					}
					if (res.substr(res.length - 1) == ',')
						res = res.substr(0, res.length - 1) + "\n";
					res += ")";
				}
			}
			res += "\n);?>";

//			if (window.lca)
//			{
//				var key = str_pad_left(++_$compLength, 4, '0');
//				_$arComponents[key] = res;
//				return '#COMPONENT'+String(key)+'#';
//			}
//			else

			return res;
		},

		GetOnDropHtml: function(params)
		{
			var _params = {
				name: params.name
			};
			return this.GetSource(_params);
		},

		CatchVariableAliases: function(str)
		{
			var
				arRes = [], i, matchRes,
				res = str.match(/(\?|&)(.+?)=#([^#]+?)#/ig);

			if (!res)
				return arRes;

			for (i = 0; i < res.length; i++)
			{
				matchRes = res[i].match(/(\?|&)(.+?)=#([^#]+?)#/i);
				arRes[matchRes[3]] = matchRes[2];
			}
			return arRes;
		},

		LoadParamsList: function(params)
		{
			oBXComponentParamsManager.LoadComponentParams(
				{
					name: params.name,
					parent: false,
					template: '',
					exParams: false,
					currentValues: {}
				}
			);
		},

		GetComponentData: function(name)
		{
			var item = this.components.items[this.compNameIndex[name]];
			return item || {};
		},

		IsPHPBracket: function(str)
		{
			return str.substr(0, 2) =='={';
		},

		TrimPHPBracket: function(str)
		{
			return str.substr(2, str.length - 3);
		},

		OnComponentDoubleClick: function(bxTag, origTag, target, e)
		{
			if (origTag && origTag.tag == 'component')
			{
				// Show dialog
				this.ShowPropertiesDialog(origTag.params, bxTag);
			}
		},

		ShowPropertiesDialog: function(component, bxTag)
		{
			// Used to prevent influence of oBXComponentParamsManager to this array...
			var comp = BX.clone(component, 1);
			if (!this.oPropertiesDialog)
			{
				//PropertiesDialog
				this.oPropertiesDialog = this.editor.GetDialog('componentProperties', {oBXComponentParamsManager: oBXComponentParamsManager});

				BX.addCustomEvent(this.oPropertiesDialog, "OnDialogSave", BX.proxy(this.SavePropertiesDialog, this));
			}

			this.currentViewedComponentTag = bxTag;
			this.oPropertiesDialog.SetTitle(BX.message('ComponentPropsTitle').replace('#COMPONENT_NAME#', comp.name));

			this.oPropertiesDialog.SetContent('<span class="bxcompprop-wait-notice">' + BX.message('ComponentPropsWait') + '</span>');
			this.oPropertiesDialog.Show();
			if (this.oPropertiesDialog.oDialog.PARTS.CONTENT_DATA)
			{
				BX.addClass(this.oPropertiesDialog.oDialog.PARTS.CONTENT_DATA, 'bxcompprop-outer-wrap');
			}

			var _this = this;
			var pParamsContainer = BX.create("DIV");

			BX.onCustomEvent(oBXComponentParamsManager, 'OnComponentParamsDisplay',
			[{
				name: comp.name,
				parent: !!comp.parentComponent,
				template: comp.template,
				exParams: comp.exParams,
				currentValues: comp.params,
				container: pParamsContainer,
				siteTemplate: this.editor.GetTemplateId(),
				relPath: this.editor.config.relPath,
				callback: function(params, container){
					_this.PropertiesDialogCallback(params, container);
				}
			}]);
		},

		PropertiesDialogCallback: function(params, container)
		{
			if (this.oPropertiesDialog.oDialog.PARTS.CONTENT_DATA)
				BX.addClass(this.oPropertiesDialog.oDialog.PARTS.CONTENT_DATA, 'bxcompprop-outer-wrap');
			this.oPropertiesDialog.SetContent(container);

			var size = this.oPropertiesDialog.GetContentSize();
			BX.onCustomEvent(oBXComponentParamsManager, 'OnComponentParamsResize', [
				size.width,
				size.height
			]);
		},

		SavePropertiesDialog: function()
		{
			var
				ddBxTag = this.currentViewedComponentTag,
				compBxTag = this.editor.GetBxTag(ddBxTag.params.origId),
				currentValues = oBXComponentParamsManager.GetParamsValues(),
				template = oBXComponentParamsManager.GetTemplateValue();

			ddBxTag.params.origParams.params = compBxTag.params.params = currentValues;
			ddBxTag.params.origParams.template = compBxTag.params.template = template;

			this.editor.synchro.FullSyncFromIframe();
		},

		ReloadList: function()
		{
			var _this = this;
			this.editor.Request({
				getData: this.editor.GetReqData('load_components_list',
					{
						site_template: this.editor.GetTemplateId(),
						componentFilter: this.editor.GetComponentFilter()
					}
				),
				handler: function(res)
				{
					_this.components = _this.editor.config.components = res;
					_this.HandleList();
					_this.editor.componentsTaskbar.BuildTree(_this.components.groups, _this.components.items);
				}
			});
		},

		SetComponentIcludeMethod: function(method)
		{
			this.componentIncludeMethod = method;
		}
	};

	function __runcomp()
	{
		window.BXHtmlEditor.BXEditorComponents = BXEditorComponents;

		function PropertiesDialog(editor, params)
		{
			params = params || {};
			params.id = 'bx_component_properties';
			params.height = 600;
			params.width =  800;
			params.resizable = true;
			this.oBXComponentParamsManager = params.oBXComponentParamsManager;

			this.id = 'components_properties';

			// Call parrent constructor
			PropertiesDialog.superclass.constructor.apply(this, [editor, params]);

			BX.addClass(this.oDialog.DIV, "bxcompprop-dialog");
			BX.addCustomEvent(this, "OnDialogSave", BX.proxy(this.Save, this));
		}
		BX.extend(PropertiesDialog, window.BXHtmlEditor.Dialog);

		PropertiesDialog.prototype.OnResize = function()
		{
			var
				w = this.oDialog.PARTS.CONTENT_DATA.offsetWidth,
				h = this.oDialog.PARTS.CONTENT_DATA.offsetHeight;

			BX.onCustomEvent(this.oBXComponentParamsManager, 'OnComponentParamsResize', [w, h]);
		};

		PropertiesDialog.prototype.OnResizeFinished = function()
		{
		};

		window.BXHtmlEditor.dialogs.componentProperties = PropertiesDialog;
	}

	if (window.BXHtmlEditor && window.BXHtmlEditor.dialogs)
		__runcomp();
	else
		BX.addCustomEvent(window, "OnEditorBaseControlsDefined", __runcomp);

})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:63:"/bitrix/js/fileman/html_editor/html-snippets.js?145227744828100";s:6:"source";s:47:"/bitrix/js/fileman/html_editor/html-snippets.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
/**
 * Bitrix HTML Editor 3.0
 * Date: 24.04.13
 * Time: 4:23
 *
 * Snippets class
 */
(function()
{
function __runsnips()
{
	function BXEditorSnippets(editor)
	{
		this.editor = editor;
		this.listLoaded = false;
		this.snippets = this.editor.config.snippets;
		this.HandleList();
		this.Init();
	}

	BXEditorSnippets.prototype = {
		Init: function()
		{
			BX.addCustomEvent(this.editor, "OnApplySiteTemplate", BX.proxy(this.OnTemplateChanged, this));
		},

		SetSnippets: function(snippets)
		{
			this.snippets =
				this.editor.config.snippets =
					this.editor.snippetsTaskbar.snippets = snippets;
			this.HandleList();
		},

		GetList: function()
		{
			return this.snippets[this.editor.GetTemplateId()];
		},

		HandleList: function()
		{
			var
				i,
				items = this.GetList().items;
			if (items)
			{
				for (i in items)
				{
					if (items.hasOwnProperty(i))
					{
						items[i].key = items[i].path.replace('/', ',');
					}
				}
			}
		},

		ReloadList: function(clearCache)
		{
			this.editor.snippetsTaskbar.ClearSearchResult();
			var _this = this;
			this.editor.Request({
				getData: this.editor.GetReqData('load_snippets_list',
					{
						site_template: this.editor.GetTemplateId(),
						clear_cache: clearCache ? 'Y' : 'N'
					}
				),
				handler: function(res)
				{
					if (res.result)
					{
						_this.SetSnippets(res.snippets);
						_this.RebuildAll();
					}
				}
			});
		},

		FetchPlainListOfCategories: function(list, level, result)
		{
			var i, l = list.length;
			for (i = 0; i < l; i++)
			{
				result.push({
					level: level,
					key: list[i].key,
					section: list[i].section
				});

				if (list[i].children && list[i].children.length > 0)
				{
					this.FetchPlainListOfCategories(list[i].children, level + 1, result);
				}
			}
		},

		AddNewCategory: function(params)
		{
			var _this = this;
			this.editor.Request({
				getData: this.editor.GetReqData('snippet_add_category',
					{
						site_template: this.editor.GetTemplateId(),
						category_name: params.name,
						category_parent: params.parent
					}
				),
				handler: function(res)
				{
					if (res.result)
					{
						_this.SetSnippets(res.snippets);
						_this.RebuildAll();
					}
				}
			});
		},

		RemoveCategory: function(params)
		{
			var _this = this;
			this.editor.Request({
				getData: this.editor.GetReqData('snippet_remove_category',
					{
						site_template: this.editor.GetTemplateId(),
						category_path: params.path
					}
				),
				handler: function(res)
				{
					if (res.result)
					{
						_this.SetSnippets(res.snippets);
						_this.RebuildAll();
					}
				}
			});
		},

		RenameCategory: function(params)
		{
			var _this = this;
			this.editor.Request({
				getData: this.editor.GetReqData('snippet_rename_category',
					{
						site_template: this.editor.GetTemplateId(),
						category_path: params.path,
						category_new_name: params.newName
					}
				),
				handler: function(res)
				{
					if (res.result)
					{
						_this.SetSnippets(res.snippets);
						_this.RebuildAll();
					}
				}
			});
		},

		SaveSnippet: function(params)
		{
			var _this = this;
			this.editor.Request({
				postData: this.editor.GetReqData('edit_snippet',
					{
						site_template: this.editor.GetTemplateId(),
						path: params.path.replace(',', '/'),
						name: params.name,
						code: params.code,
						description: params.description,
						current_path: params.currentPath
					}
				),
				handler: function(res)
				{
					if (res.result)
					{
						_this.SetSnippets(res.snippets);
						_this.RebuildAll();
					}
				}
			});
		},

		RemoveSnippet: function(params)
		{
			var _this = this;
			this.editor.Request({
				getData: this.editor.GetReqData('remove_snippet',
					{
						site_template: this.editor.GetTemplateId(),
						path: params.path.replace(',', '/')
					}
				),
				handler: function(res)
				{
					if (res.result)
					{
						_this.SetSnippets(res.snippets);
						_this.RebuildAll();
					}
				}
			});
		},

		RebuildAll: function()
		{
			var snippetsCategories = this.editor.GetDialog('snippetsCategories');
			if (snippetsCategories && snippetsCategories.IsOpen())
			{
				snippetsCategories.DisplayAddForm(false);
				snippetsCategories.BuildTree(this.GetList().groups);
			}

			// Build structure
			if (this.snippets[this.editor.GetTemplateId()] && this.editor.snippetsTaskbar)
			{
				this.editor.snippetsTaskbar.BuildTree(this.snippets[this.editor.GetTemplateId()].groups, this.snippets[this.editor.GetTemplateId()].items);
			}

			var editSnippet = this.editor.GetDialog('editSnippet');
			if (editSnippet && editSnippet.IsOpen())
			{
				editSnippet.SetCategories();
			}
		},

		OnTemplateChanged: function(templateId)
		{
			this.ReloadList(false);
		}
	};

	function SnippetsControl(editor)
	{
		// Call parrent constructor
		SnippetsControl.superclass.constructor.apply(this, arguments);

		this.id = 'snippets';
		this.snippets = this.editor.config.snippets;
		this.templateId = this.editor.templateId;
		this.title = BX.message('BXEdSnippetsTitle');
		this.searchPlaceholder = BX.message('BXEdSnipSearchPlaceHolder');
		this.uniqueId = 'taskbar_' + this.editor.id + '_' + this.id;

		this.Init();
	}

	BX.extend(SnippetsControl, window.BXHtmlEditor.Taskbar);

	SnippetsControl.prototype.Init = function()
	{
		this.BuildSceleton();

		// Build structure
		if (this.snippets[this.templateId])
		{
			this.BuildTree(this.snippets[this.templateId].groups, this.snippets[this.templateId].items);
		}

		var _this = this;
		_this.editor.phpParser.AddBxNode('snippet_icon',
			{
				Parse: function(params)
				{
					return params.code || '';
				}
			}
		);
	};

	SnippetsControl.prototype.GetMenuItems = function()
	{
		var _this = this;

		var arItems = [
			{
				text : BX.message('BXEdAddSnippet'),
				title : BX.message('BXEdAddSnippet'),
				className : "",
				onclick: function()
				{
					_this.editor.GetDialog('editSnippet').Show();
					BX.PopupMenu.destroy(_this.uniqueId + "_menu");
				}
			},
			{
				text : BX.message('RefreshTaskbar'),
				title : BX.message('RefreshTaskbar'),
				className : "",
				onclick: function()
				{
					_this.editor.snippets.ReloadList(true);
					BX.PopupMenu.destroy(_this.uniqueId + "_menu");
				}
			},
			{
				text : BX.message('BXEdManageCategories'),
				title : BX.message('BXEdManageCategories'),
				className : "",
				onclick: function()
				{
					_this.editor.GetDialog('snippetsCategories').Show();
					BX.PopupMenu.destroy(_this.uniqueId + "_menu");
				}
			}
		]
		return arItems;
	};

	SnippetsControl.prototype.HandleElementEx = function(wrap, dd, params)
	{
		this.editor.SetBxTag(dd, {tag: "snippet_icon", params: params});
		wrap.title = params.description || params.title;

		var editBut = wrap.appendChild(BX.create("SPAN", {props: {className: "bxhtmled-right-side-item-edit-btn", title: BX.message('BXEdSnipEdit')}}));
		this.editor.SetBxTag(editBut, {tag: "_snippet", params: params});

		BX.bind(editBut, 'mousedown', BX.proxy(this.EditSnippet, this));
	};

	SnippetsControl.prototype.EditSnippet = function(e)
	{
		var target = e.target || e.srcElement;

		function _editDeactivate()
		{
			BX.removeClass(target, 'bxhtmled-right-side-item-edit-btn-active');
			BX.unbind(document, 'mouseup', _editDeactivate);
		}

		BX.addClass(target, 'bxhtmled-right-side-item-edit-btn-active');
		BX.bind(document, 'mouseup', _editDeactivate);

		this.editor.GetDialog('editSnippet').Show(target);
		return BX.PreventDefault(e);
	};

	SnippetsControl.prototype.BuildTree = function(sections, elements)
	{
		// Call parent method
		SnippetsControl.superclass.BuildTree.apply(this, arguments);
		if ((!sections || sections.length == 0) && (!elements || elements.length == 0))
		{
			this.pTreeCont.appendChild(BX.create('DIV', {props: {className: 'bxhtmled-no-snip'}, text: BX.message('BXEdSnipNoSnippets')}));
		}
	};

	function EditSnippetDialog(editor, params)
	{
		params = params || {};
		params.id = 'bx_edit_snippet';
		params.width =  600;
		this.zIndex = 3007;
		this.id = 'edit_snippet';

		// Call parrent constructor
		EditSnippetDialog.superclass.constructor.apply(this, [editor, params]);
		this.SetContent(this.Build());
		BX.addClass(this.oDialog.DIV, "bx-edit-snip-dialog");
		BX.addCustomEvent(this, "OnDialogSave", BX.proxy(this.Save, this));
	}
	BX.extend(EditSnippetDialog, window.BXHtmlEditor.Dialog);

	EditSnippetDialog.prototype.Save = function()
	{
		this.editor.snippets.SaveSnippet(
			{
				path: this.pCatSelect.value,
				name: this.pName.value,
				code: this.pCode.value,
				description: this.pDesc.value,
				currentPath: this.currentPath
			}
		);
	};

	EditSnippetDialog.prototype.Build = function()
	{
		this.pCont = BX.create('DIV', {props: {className: 'bxhtmled-edit-snip-cnt'}});

		function addRow(tbl, c1Par, bAdditional)
		{
			var r, c1, c2;

			r = tbl.insertRow(-1);
			if (bAdditional)
			{
				r.className = 'bxhtmled-add-row';
			}

			c1 = r.insertCell(-1);
			c1.className = 'bxhtmled-left-c';

			if (c1Par && c1Par.label)
			{
				c1.appendChild(BX.create('LABEL', {props: {className: c1Par.required ? 'bxhtmled-req' : ''},text: c1Par.label})).setAttribute('for', c1Par.id);
			}

			c2 = r.insertCell(-1);
			c2.className = 'bxhtmled-right-c';
			return {row: r, leftCell: c1, rightCell: c2};
		}

		this.arTabs = [
			{
				id: 'base',
				name: BX.message('BXEdSnipBaseSettings')
			},
			{
				id: 'additional',
				name: BX.message('BXEdSnipAddSettings')
			}
		];


		var res = this.BuildTabControl(this.pCont, this.arTabs);
		this.arTabs = res.tabs;

		// Base params
		var
			_this = this,
			r, c,
			pBaseTbl = BX.create('TABLE', {props: {className: 'bxhtmled-dialog-tbl'}}),
			pAddTbl = BX.create('TABLE', {props: {className: 'bxhtmled-dialog-tbl'}});

		// Name
		r = addRow(pBaseTbl, {label: BX.message('BXEdSnipName') + ':', id: this.id + '-name', required: true});
		this.pName = r.rightCell.appendChild(BX.create('INPUT', {props:
		{
			type: 'text',
			id: this.id + '-name',
			placeholder: BX.message('BXEdSnipNamePlaceHolder')
		}}));

		// Code
		r = addRow(pBaseTbl, {label: BX.message('BXEdSnipCode') + ':', id: this.id + '-code', required: true});
		this.pCode = r.rightCell.appendChild(BX.create('TEXTAREA', {
			props:
			{
				id: this.id + '-code',
				placeholder: BX.message('BXEdSnipCodePlaceHolder')
			},
			style:
			{
				height: '250px'
			}
		}));
		this.arTabs[0].cont.appendChild(pBaseTbl);

		//r.className = 'bxhtmled-add-row';
		//c1.className = 'bxhtmled-left-c';


		//BX.bind(this.pCatSelect, "change", BX.proxy(this.ChangeCat, this));

		// Additional params
		// Site templatesnippet_remove_category
//		r = addRow(pAddTbl, {label: BX.message('BXEdSnipSiteTemplate') + ':', id: this.id + '-template'});
//		this.pTemplate = r.rightCell.appendChild(BX.create('SELECT', {props:
//		{
//			id: this.id + '-template'
//		}}));

		// File name
//		r = addRow(pAddTbl, {label: BX.message('BXEdSnipFileName') + ':', id: this.id + '-file-name'});
//		this.pFileName = r.rightCell.appendChild(BX.create('INPUT', {props:
//			{
//				type: 'text',
//				id: this.id + '-file-name'
//			},
//			style: {
//				width: '150px',
//				textAlign: 'right'
//			}
//		}));
//		r.rightCell.appendChild(document.createTextNode(' .snp'));

		// Category
		r = addRow(pAddTbl, {label: BX.message('BXEdSnipCategory') + ':', id: this.id + '-category'});
		this.pCatSelect = r.rightCell.appendChild(BX.create('SELECT', {
			props: {
				id: this.id + '-category'
			},
			style: {
				maxWidth: '280px'
			}
		}));
		this.pCatManageBut = r.rightCell.appendChild(BX.create('INPUT', {props:
		{
			className: 'bxhtmled-manage-cat',
			type: 'button',
			value: '...',
			title: BX.message('BXEdManageCategories')
		}}));
		this.pCatManageBut.onclick = function()
		{
			_this.editor.GetDialog('snippetsCategories').Show();
		};

		// Description
		r = addRow(pAddTbl, {label: BX.message('BXEdSnipDescription') + ':', id: this.id + '-hint'});
		this.pDesc = r.rightCell.appendChild(BX.create('TEXTAREA', {props:
		{
			id: this.id + '-hint',
			placeholder: BX.message('BXEdSnipDescriptionPlaceholder')
		}}));
		this.arTabs[1].cont.appendChild(pAddTbl);

		// Delete button
		r = BX.adjust(pAddTbl.insertRow(-1), {style: {display: 'none'}});
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled--centr-c'}, attrs: {colsPan: 2}});
		c.appendChild(BX.create("INPUT", {
			props:{className: '', type: 'button', value: BX.message('BXEdSnipRemove')},
			events: {
				'click' : function()
				{
					if (confirm(BX.message('BXEdSnipRemoveConfirm')))
					{
						_this.editor.snippets.RemoveSnippet({path: _this.currentPath});
						_this.Close();
					}
				}
			}
		}));
		this.delSnipRow = r;

		return this.pCont;
	};

	EditSnippetDialog.prototype.Show = function(snippetNode)
	{
		this.SetTitle(BX.message('BXEdEditSnippetDialogTitle'));
		this.SetCategories();

		var
			params = {},
			bxTag = this.editor.GetBxTag(snippetNode),
			bNew = !bxTag || !bxTag.tag;

		if (!bNew)
		{
			params = bxTag.params;
			this.currentPath = (params.path == '' ? '' : params.path.replace(',', '/') + '/') + params.name;
			this.delSnipRow.style.display = '';
		}
		else
		{
			this.currentPath = '';
			this.delSnipRow.style.display = 'none';
		}

		this.pName.value = params.title || '';
		this.pCode.value = params.code || '';
		this.pDesc.value = params.description || '';
		this.pCatSelect.value = params.key || '';

		// Call parrent Dialog.Show()
		EditSnippetDialog.superclass.Show.apply(this, arguments);
	};

	EditSnippetDialog.prototype.SetCategories = function()
	{
		// Clear select
		this.pCatSelect.options.length = 0;
		this.pCatSelect.options.add(new Option(BX.message('BXEdSnippetsTitle'), '', true, true));

		var
			name, delim = ' . ', j, i,
			plainList = [],
			list = this.editor.snippetsTaskbar.GetSectionsTreeInfo();

		this.editor.snippets.FetchPlainListOfCategories(list, 1, plainList);

		for (i = 0; i < plainList.length; i++)
		{
			name = '';
			for (j = 0; j < plainList[i].level; j++)
			{
				name += delim;
			}
			name += plainList[i].section.name;

			this.pCatSelect.options.add(new Option(name, plainList[i].key, false, false));
		}
	};

	EditSnippetDialog.prototype.ChangeCat = function(changeFileName)
	{
		var
			defFilename = '',
			path = this.pCatSelect.value;

//		changeFileName = changeFileName !== false;
//		if (path == '')
//		{
//			defFilename = this.editor.snippets.GetList().rootDefaultFilename;
//		}
//		else
//		{
//			var section = this.editor.snippetsTaskbar.treeSectionIndex[path];
//			if (section && section.sect  && section.sect.section)
//			{
//				defFilename = section.sect.section.default_name;
//			}
//		}
//
//		if (changeFileName && defFilename)
//		{
//			this.pFileName.value = defFilename;
//		}
	};


	function SnippetsCategoryDialog(editor, params)
	{
		params = params || {};
		params.id = 'bx_snippets_cats';
		//params.height = 600;
		params.width =  400;
		params.zIndex = 3010;

		this.id = 'snippet_categories';

		// Call parrent constructor
		SnippetsCategoryDialog.superclass.constructor.apply(this, [editor, params]);
		this.SetContent(this.Build());

		this.oDialog.ClearButtons();
		this.oDialog.SetButtons([this.oDialog.btnClose]);

		BX.addClass(this.oDialog.DIV, "bx-edit-snip-cat-dialog");
		//BX.addCustomEvent(this, "OnDialogSave", BX.proxy(this.Save, this));
	}
	BX.extend(SnippetsCategoryDialog, window.BXHtmlEditor.Dialog);

	SnippetsCategoryDialog.prototype.Save = function()
	{
	};

	SnippetsCategoryDialog.prototype.Build = function()
	{
		this.pCont = BX.create('DIV', {props: {className: 'bxhtmled-snip-cat-cnt'}});

		// Add category button & wrap
		this.pAddCatWrap = this.pCont.appendChild(BX.create('DIV', {props: {className: 'bxhtmled-snip-cat-add-wrap'}}));
		this.pAddCatBut = this.pAddCatWrap.appendChild(BX.create('SPAN', {props: {className: 'bxhtmled-snip-cat-add-but'}, text: BX.message('BXEdSnipCatAdd')}));
		BX.bind(this.pAddCatBut, 'click', BX.proxy(this.DisplayAddForm, this));

		var tbl = this.pAddCatWrap.appendChild(BX.create('TABLE', {props: {className: 'bxhtmled-snip-cat-add-tbl'}}));
		var r, c;
		r = tbl.insertRow(-1);
		c = r.insertCell(-1);
		c.className = 'bxhtmled-left-c';
		c.appendChild(BX.create('LABEL', {props: {className: 'bxhtmled-req'}, attrs: {'for': this.id + '-cat-name'}, text: BX.message('BXEdSnipCatAddName') + ':'}));

		c = r.insertCell(-1);
		c.className = 'bxhtmled-right-c';
		this.pCatName = c.appendChild(BX.create('INPUT', {props:
		{
			type: 'text',
			id: this.id + '-cat-name'
		}}));

		r = tbl.insertRow(-1);
		c = r.insertCell(-1);
		c.className = 'bxhtmled-left-c';
		c.appendChild(BX.create('LABEL', {props: {className: 'bxhtmled-req'}, attrs: {'for': this.id + '-cat-par'}, text: BX.message('BXEdSnipParCategory') + ':'}));

		c = r.insertCell(-1);
		c.className = 'bxhtmled-right-c';
		this.pCatPar = c.appendChild(BX.create('SELECT', {props:{id: this.id + '-cat-par'}}));

		r = tbl.insertRow(-1);
		c = r.insertCell(-1);
		c.colSpan = 2;
		c.style.textAlign = 'center';

		this.pSaveCat = c.appendChild(BX.create('INPUT', {props:
		{
			type: 'button',
			className: 'adm-btn-save bxhtmled-snip-save-but',
			value: BX.message('BXEdSnipCatAddBut')
		}}));
		BX.bind(this.pSaveCat, 'click', BX.proxy(this.AddNewCategory, this));

		// Category List
		this.pCatListWrap = this.pCont.appendChild(BX.create('DIV', {props: {className: 'bxhtmled-snip-cat-list-wrap'}}));

		return this.pCont;
	};

	SnippetsCategoryDialog.prototype.AddNewCategory = function()
	{
		this.editor.snippets.AddNewCategory({
			name: this.pCatName.value,
			parent: this.pCatPar.value,
			siteTemplate: ''
		});
	};


	SnippetsCategoryDialog.prototype.DisplayAddForm = function(bShow)
	{
		if (this.animation)
			this.animation.stop();

		if (bShow !== true && bShow !== false)
			bShow = !this.bAddCatOpened;

		bShow = bShow !== false;

		if (this.bAddCatOpened !== bShow)
		{
			if (bShow)
			{
				//jsDD.Disable();
				this.DisableKeyCheck();
				BX.bind(this.pCatName, 'keydown', BX.proxy(this.AddCatKeydown, this));

				this.SetParentCategories();
				this.animationStartHeight = 25;
				this.animationEndHeight = 160;
				BX.focus(this.pCatName);
			}
			else
			{
				//jsDD.Enable();
				this.EnableKeyCheck();
				BX.unbind(this.pCatName, 'keydown', BX.proxy(this.AddCatKeydown, this));
				this.animationStartHeight = 160;
				this.animationEndHeight = 25;
			}

			var _this = this;
			this.animation = new BX.easing({
				duration : 300,
				start : {height: this.animationStartHeight},
				finish : {height: this.animationEndHeight},
				transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),

				step : function(state)
				{
					_this.pAddCatWrap.style.height = state.height + 'px';
				},

				complete : BX.proxy(function()
				{
					this.animation = null;
				}, this)
			});

			this.animation.animate();
			this.bAddCatOpened = bShow;
		}
		this.ResetAddCategoryForm();
	};

	SnippetsCategoryDialog.prototype.SetParentCategories = function()
	{
		// Clear select
		this.pCatPar.options.length = 0;
		this.pCatPar.options.add(new Option(BX.message('BXEdSnippetsTitle'), '', true, true));

		var
			name, delim = ' . ', j, i,
			plainList = [],
			list = this.editor.snippetsTaskbar.GetSectionsTreeInfo();

		this.editor.snippets.FetchPlainListOfCategories(list, 1, plainList);

		for (i = 0; i < plainList.length; i++)
		{
			if (plainList[i].level < 2)
			{
				name = '';
				for (j = 0; j < plainList[i].level; j++)
				{
					name += delim;
				}
				name += plainList[i].section.name;

				this.pCatPar.options.add(new Option(name, plainList[i].key, false, false));
			}
		}
	};

	SnippetsCategoryDialog.prototype.Show = function()
	{
		this.SetTitle(BX.message('BXEdManageCategoriesTitle'));

		this.BuildTree(this.editor.snippets.GetList().groups);
		this.bAddCatOpened = false;
		this.pAddCatWrap.style.height = '';

		// Call parrent Dialog.Show()
		SnippetsCategoryDialog.superclass.Show.apply(this, arguments);
	};

	SnippetsCategoryDialog.prototype.BuildTree = function(sections)
	{
		BX.cleanNode(this.pCatListWrap);
		this.catIndex = {};
		//this.sections = [];
		for (var i = 0; i < sections.length; i++)
		{
			this.BuildCategory(sections[i]);
		}
	};

	SnippetsCategoryDialog.prototype.BuildCategory = function(section)
	{
		var
			_this = this,
			parentCont = this.GetCategoryContByPath(section.path),
			pGroup = BX.create("DIV", {props: {className: "bxhtmled-tskbr-sect-outer"}}),
			pGroupTitle = pGroup.appendChild(BX.create("DIV", {props: {className: "bxhtmled-tskbr-sect"}})),
			icon = pGroupTitle.appendChild(BX.create("SPAN", {props: {className: "bxhtmled-tskbr-sect-icon bxhtmled-tskbr-sect-icon-open"}})),
			title = pGroupTitle.appendChild(BX.create("SPAN", {props: {className: "bxhtmled-tskbr-sect-title"}, text: section.title || section.name})),
			renameInput = pGroupTitle.appendChild(BX.create("INPUT", {props: {
				type: 'text',
				className: "bxhtmled-tskbr-name-input"
			}})),
			childCont = pGroup.appendChild(BX.create("DIV", {props: {className: "bxhtmled-tskb-child"}, style: {display: "block"}})),
			pIconEdit = pGroupTitle.appendChild(BX.create("SPAN", {props: {className: "bxhtmled-right-side-item-edit-btn", title: BX.message('BXEdSnipCatEdit')}})),
			pIconDel = pGroupTitle.appendChild(BX.create("SPAN", {props: {className: "bxhtmled-right-side-item-del-btn", title: BX.message('BXEdSnipCatDelete')}}));

		BX.bind(pIconDel, 'mousedown', BX.proxy(this.DisableDD(), this));
		BX.bind(pIconEdit, 'mousedown', BX.proxy(this.DisableDD(), this));
		BX.bind(renameInput, 'mousedown', BX.proxy(this.DisableDD(), this));

		// Drop category
		BX.bind(pIconDel, 'click', function()
			{
				if (confirm(BX.message('BXEdDropCatConfirm')))
				{
					var path = section.path == '' ? section.name : section.path + '/' + section.name;
					_this.editor.snippets.RemoveCategory({path: path});
				}
			}
		);

		// Rename category
		BX.bind(pIconEdit, 'click', function()
			{
				_this.ShowRename(true, section, renameInput, pGroupTitle);
			}
		);

		childCont.style.display = 'block';

		var key = section.path == '' ? section.name : section.path + ',' + section.name;
		var depth = section.path == '' ? 0 : 1;

		var sect = {
			key: key,
			children: [],
			section: section
		};

		this.catIndex[key] = {
			icon: icon,
			outerCont: pGroup,
			cont: pGroupTitle,
			childCont: childCont,
			sect: sect
		};

		if (depth > 0)
		{
			BX.addClass(pGroupTitle, "bxhtmled-tskbr-sect-" + depth);
			BX.addClass(icon, "bxhtmled-tskbr-sect-icon-" + depth);
		}

		this.InitDragDrop({
			group: pGroupTitle
		});
//		pGroup.setAttribute('data-bx-type', 'taskbargroup');
//		pGroup.setAttribute('data-bx-path', key);
//		pGroup.setAttribute('data-bx-taskbar', this.id);

		parentCont.appendChild(pGroup);
	};

	SnippetsCategoryDialog.prototype.ShowRename = function(bShow, section, renameInput, pGroupTitle)
	{
		bShow = bShow !== false;
		if (bShow)
		{
			BX.addClass(pGroupTitle, 'bxhtmled-tskbr-sect-rename');
			this.currentRenamedCat = {
				section: section,
				renameInput: renameInput,
				pGroupTitle: pGroupTitle
			};
			renameInput.value = section.name;
			//jsDD.Disable();
			this.DisableKeyCheck();
			BX.bind(renameInput, 'keydown', BX.proxy(this.RenameKeydown, this));
			BX.focus(renameInput);
			renameInput.select();
		}
		else
		{
			BX.removeClass(pGroupTitle, 'bxhtmled-tskbr-sect-rename');
			BX.unbind(renameInput, 'keydown', BX.proxy(this.RenameKeydown, this));
			//jsDD.Enable();
			this.EnableKeyCheck();
			this.currentRenamedCat = false;
		}
	};

	SnippetsCategoryDialog.prototype.RenameKeydown = function(e)
	{
		if (e && this.currentRenamedCat)
		{
			if (e.keyCode == this.editor.KEY_CODES['escape'])
			{
				this.ShowRename(false, this.currentRenamedCat.section, this.currentRenamedCat.renameInput, this.currentRenamedCat.pGroupTitle);
				BX.PreventDefault(e);
			}
			else if (e.keyCode == this.editor.KEY_CODES['enter'])
			{
				var
					newName = BX.util.trim(this.currentRenamedCat.renameInput.value),
					section = this.currentRenamedCat.section,
					path = section.path == '' ? section.name : section.path + '/' + section.name;

				if (newName !== '')
				{
					this.editor.snippets.RenameCategory(
					{
						path: path,
						newName: newName
					});
				}
				this.ShowRename(false, this.currentRenamedCat.section, this.currentRenamedCat.renameInput, this.currentRenamedCat.pGroupTitle);
				BX.PreventDefault(e);
			}
		}
	};

	SnippetsCategoryDialog.prototype.AddCatKeydown = function(e)
	{
		if (e && this.bAddCatOpened)
		{
			if (e.keyCode == this.editor.KEY_CODES['escape'])
			{
				this.DisplayAddForm(false);
				BX.PreventDefault(e);
			}
			else if (e.keyCode == this.editor.KEY_CODES['enter'])
			{
				this.AddNewCategory();
				BX.PreventDefault(e);
			}
		}
	};

	SnippetsCategoryDialog.prototype.DisableDD = function()
	{
		jsDD.Disable();
		BX.bind(document, 'mouseup', BX.proxy(this.EnableDD, this));
	};

	SnippetsCategoryDialog.prototype.EnableDD = function()
	{
		jsDD.Enable();
		BX.unbind(document, 'mouseup', BX.proxy(this.EnableDD, this));
	};

	SnippetsCategoryDialog.prototype.InitDragDrop = function(params)
	{
		// TODO: Do correct drag & drop + sorting of categories
		return;
		var
			_this = this,
			obj = params.group;
		jsDD.registerObject(obj);

		obj.style.cursor = 'move';
		obj.onbxdragstart = function()
		{
			_this.dragCat = obj.cloneNode(true);
			BX.addClass(obj, 'bxhtmled-tskbr-sect-old');
			BX.addClass(_this.dragCat, 'bxhtmled-tskbr-sect-drag');
			document.body.appendChild(_this.dragCat);
			_this.dragCat.style.top = '-1000px';
			_this.dragCat.style.left = '-1000px';
		};

		obj.onbxdrag = function(x, y)
		{
			if (_this.dragCat)
			{
				_this.dragCat.style.left = (x - 20) + 'px';
				_this.dragCat.style.top = (y - 10) + 'px';
			}
		};

		obj.onbxdragstop = function(x, y)
		{
			if (_this.dragCat)
			{
				setTimeout(function()
				{
					BX.remove(_this.dragCat);
					_this.dragCat = null;
				}, 100);
			}
			_this.OnDragFinish();
		};

		obj.onbxdragfinish = function(destination, x, y)
		{
			_this.OnDragFinish();
			return true;
		};

		jsDD.registerDest(obj);


		obj.onbxdestdragfinish = function(currentNode, x, y)
		{
			var
				pos = BX.pos(obj),
				beforeNode = y < pos.top + pos.height / 2;

			if (beforeNode)
			{
				BX.addClass(obj, 'bxhtmled-tskbr-sect-dest-top');
				BX.removeClass(obj, 'bxhtmled-tskbr-sect-dest-bottom');
			}
			else
			{
				BX.addClass(obj, 'bxhtmled-tskbr-sect-dest-bottom');
				BX.removeClass(obj, 'bxhtmled-tskbr-sect-dest-top');
			}

			return true;
		};

		obj.onbxdestdraghover = function(currentNode, x, y)
		{
			var pos = BX.pos(obj);
			if (y < pos.top + pos.height / 2)
			{
				BX.addClass(obj, 'bxhtmled-tskbr-sect-dest-top');
				BX.removeClass(obj, 'bxhtmled-tskbr-sect-dest-bottom');
			}
			else
			{
				BX.addClass(obj, 'bxhtmled-tskbr-sect-dest-bottom');
				BX.removeClass(obj, 'bxhtmled-tskbr-sect-dest-top');
			}
		};
		obj.onbxdestdraghout = function(currentNode, x, y)
		{
			BX.removeClass(obj, 'bxhtmled-tskbr-sect-dest-bottom');
			BX.removeClass(obj, 'bxhtmled-tskbr-sect-dest-top');
		};
	};

	SnippetsCategoryDialog.prototype.OnDragFinish = function()
	{
	};


	SnippetsCategoryDialog.prototype.GetCategoryContByPath = function(path)
	{
		if (path == '' || !this.catIndex[path])
		{
			return this.pCatListWrap;
		}
		else
		{
			return this.catIndex[path].childCont;
		}
	};

	SnippetsCategoryDialog.prototype.ResetAddCategoryForm = function(path)
	{
		this.pCatName.value = '';
		this.pCatPar.value = '';
	};


	window.BXHtmlEditor.SnippetsControl = SnippetsControl;
	window.BXHtmlEditor.BXEditorSnippets = BXEditorSnippets;
	window.BXHtmlEditor.dialogs.editSnippet = EditSnippetDialog;
	window.BXHtmlEditor.dialogs.snippetsCategories = SnippetsCategoryDialog;
}

	if (window.BXHtmlEditor && window.BXHtmlEditor.dialogs)
		__runsnips();
	else
		BX.addCustomEvent(window, "OnEditorBaseControlsDefined", __runsnips);

})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:65:"/bitrix/js/fileman/html_editor/html-editor.min.js?145227744868327";s:6:"source";s:45:"/bitrix/js/fileman/html_editor/html-editor.js";s:3:"min";s:49:"/bitrix/js/fileman/html_editor/html-editor.min.js";s:3:"map";s:49:"/bitrix/js/fileman/html_editor/html-editor.map.js";}"*/
(function(t){var e;function i(t){this.InitUtil();this.dom={};this.bxTags={};this.EMPTY_IMAGE_SRC="/bitrix/images/1.gif";this.HTML5_TAGS=["abbr","article","aside","audio","bdi","canvas","command","datalist","details","figcaption","figure","footer","header","hgroup","keygen","mark","meter","nav","output","progress","rp","rt","ruby","svg","section","source","summary","time","track","video","wbr"];this.BLOCK_TAGS=["H1","H2","H3","H4","H5","H6","P","BLOCKQUOTE","DIV","SECTION","PRE"];this.NESTED_BLOCK_TAGS=["BLOCKQUOTE","DIV"];this.TABLE_TAGS=["TD","TR","TH","TABLE","TBODY","CAPTION","COL","COLGROUP","TFOOT","THEAD"];this.BBCODE_TAGS=["U","TABLE","TR","TD","TH","IMG","A","CENTER","LEFT","RIGHT","JUSTIFY"];this.HTML_ENTITIES=["&iexcl;","&cent;","&pound;","&curren;","&yen;","&brvbar;","&sect;","&uml;","&copy;","&ordf;","&laquo;","&not;","&reg;","&macr;","&deg;","&plusmn;","&sup2;","&sup3;","&acute;","&micro;","&para;","&middot;","&cedil;","&sup1;","&ordm;","&raquo;","&frac14;","&frac12;","&frac34;","&iquest;","&Agrave;","&Aacute;","&Acirc;","&Atilde;","&Auml;","&Aring;","&AElig;","&Ccedil;","&Egrave;","&Eacute;","&Ecirc;","&Euml;","&Igrave;","&Iacute;","&Icirc;","&Iuml;","&ETH;","&Ntilde;","&Ograve;","&Oacute;","&Ocirc;","&Otilde;","&Ouml;","&times;","&Oslash;","&Ugrave;","&Uacute;","&Ucirc;","&Uuml;","&Yacute;","&THORN;","&szlig;","&agrave;","&aacute;","&acirc;","&atilde;","&auml;","&aring;","&aelig;","&ccedil;","&egrave;","&eacute;","&ecirc;","&euml;","&igrave;","&iacute;","&icirc;","&iuml;","&eth;","&ntilde;","&ograve;","&oacute;","&ocirc;","&otilde;","&ouml;","&divide;","&oslash;","&ugrave;","&uacute;","&ucirc;","&uuml;","&yacute;","&thorn;","&yuml;","&OElig;","&oelig;","&Scaron;","&scaron;","&Yuml;","&circ;","&tilde;","&ndash;","&mdash;","&lsquo;","&rsquo;","&sbquo;","&ldquo;","&rdquo;","&bdquo;","&dagger;","&Dagger;","&permil;","&lsaquo;","&rsaquo;","&euro;","&Alpha;","&Beta;","&Gamma;","&Delta;","&Epsilon;","&Zeta;","&Eta;","&Theta;","&Iota;","&Kappa;","&Lambda;","&Mu;","&Nu;","&Xi;","&Omicron;","&Pi;","&Rho;","&Sigma;","&Tau;","&Upsilon;","&Phi;","&Chi;","&Psi;","&Omega;","&alpha;","&beta;","&gamma;","&delta;","&epsilon;","&zeta;","&eta;","&theta;","&iota;","&kappa;","&lambda;","&mu;","&nu;","&xi;","&omicron;","&pi;","&rho;","&sigmaf;","&sigma;","&tau;","&upsilon;","&phi;","&chi;","&psi;","&omega;","&bull;","&hellip;","&prime;","&Prime;","&oline;","&frasl;","&trade;","&larr;","&uarr;","&rarr;","&darr;","&harr;","&part;","&sum;","&minus;","&radic;","&infin;","&int;","&asymp;","&ne;","&equiv;","&le;","&ge;","&loz;","&spades;","&clubs;","&hearts;"];if(!BX.browser.IsIE()){this.HTML_ENTITIES=this.HTML_ENTITIES.concat(["&thetasym;","&upsih;","&piv;","&weierp;","&image;","&real;","&alefsym;","&crarr;","&lArr;","&uArr;","&rArr;","&dArr;","&hArr;","&forall;","&exist;","&empty;","&nabla;","&isin;","&notin;","&ni;","&prod;","&lowast;","&prop;","&ang;","&and;","&or;","&cap;","&cup;","&there4;","&sim;","&cong;","&sub;","&sup;","&nsub;","&sube;","&supe;","&oplus;","&otimes;","&perp;","&sdot;","&lceil;","&rceil;","&lfloor;","&rfloor;","&lang;","&rang;","&diams;"])}this.SHORTCUTS={66:"bold",73:"italic",85:"underline"};this.KEY_CODES={backspace:8,enter:13,escape:27,space:32,"delete":46,left:37,right:39,up:38,down:40,z:90,y:89,shift:16,ctrl:17,alt:18,cmd:91,cmdRight:93,pageUp:33,pageDown:34};this.INVISIBLE_SPACE="\ufeff";this.INVISIBLE_CURSOR="\u2060";this.NORMAL_WIDTH=1020;this.MIN_WIDTH=700;this.MIN_HEIGHT=100;this.MAX_HANDLED_FORMAT_LENGTH=5e4;this.MAX_HANDLED_FORMAT_TIME=500;this.iframeCssText="";this.InitConfig(this.CheckConfig(t));if(!t.lazyLoad){this.Init()}}i.prototype={Init:function(){if(this.inited)return;this.parser=new BXHtmlEditor.BXEditorParser(this);this.On("OnEditorInitedBefore",[this]);this.BuildSceleton();this.HTMLStyler=r;this.dom.textarea=this.dom.textareaCont.appendChild(BX.create("TEXTAREA",{props:{className:"bxhtmled-textarea"}}));this.dom.pValueInput=BX("bxed_"+this.id);if(!this.dom.pValueInput){this.dom.pValueInput=this.dom.cont.appendChild(BX.create("INPUT",{props:{type:"hidden",id:"bxed_"+this.id,name:this.config.inputName}}))}this.dom.pValueInput.value=this.config.content;this.dom.form=this.dom.textarea.form||false;this.document=null;this.sandbox=this.CreateIframeSandBox();var t=this.sandbox.GetIframe();t.style.width="100%";t.style.height="100%";this.textareaView=new BXEditorTextareaView(this,this.dom.textarea,this.dom.textareaCont);this.iframeView=new BXEditorIframeView(this,this.dom.textarea,this.dom.iframeCont);this.synchro=new BXEditorViewsSynchro(this,this.textareaView,this.iframeView);if(this.bbCode){this.bbParser=new BXHtmlEditor.BXEditorBbCodeParser(this)}this.phpParser=new BXHtmlEditor.BXEditorPhpParser(this);this.components=new BXHtmlEditor.BXEditorComponents(this);this.styles=new h(this);this.overlay=new BXHtmlEditor.Overlay(this);this.BuildToolbar();if(this.showTaskbars){this.taskbarManager=new BXHtmlEditor.TaskbarManager(this,true);if(this.showComponents){this.componentsTaskbar=new BXHtmlEditor.ComponentsControl(this);this.taskbarManager.AddTaskbar(this.componentsTaskbar)}if(this.showSnippets){this.snippets=new BXHtmlEditor.BXEditorSnippets(this);this.snippetsTaskbar=new BXHtmlEditor.SnippetsControl(this,this.taskbarManager);this.taskbarManager.AddTaskbar(this.snippetsTaskbar)}this.taskbarManager.ShowTaskbar(this.showComponents?this.componentsTaskbar.GetId():this.snippetsTaskbar.GetId())}else{this.dom.taskbarCont.style.display="none"}this.contextMenu=new BXHtmlEditor.ContextMenu(this);if(this.config.showNodeNavi){this.nodeNavi=new BXHtmlEditor.NodeNavigator(this);this.nodeNavi.Show()}this.InitEventHandlers();this.ResizeSceleton();if(this.showTaskbars&&this.config.taskbarShown){this.taskbarManager.Show(false)}this.inited=true;this.On("OnEditorInitedAfter",[this]);if(!this.CheckBrowserCompatibility()){this.dom.cont.parentNode.insertBefore(BX.create("DIV",{props:{className:"bxhtmled-warning"},text:BX.message("BXEdInvalidBrowser")}),this.dom.cont)}this.Show();BX.onCustomEvent(BXHtmlEditor,"OnEditorCreated",[this])},InitConfig:function(t){this.config=t;this.id=this.config.id;this.dialogs={};this.bbCode=!!this.config.bbCode;this.config.splitVertical=!!this.config.splitVertical;this.config.splitRatio=parseFloat(this.config.splitRatio);this.config.view=this.config.view||"wysiwyg";this.config.taskbarShown=!!this.config.taskbarShown;this.config.taskbarWidth=parseInt(this.config.taskbarWidth);this.config.showNodeNavi=this.config.showNodeNavi!==false;this.config.setFocusAfterShow=this.config.setFocusAfterShow!==false;this.cssCounter=0;this.iframeCssText=this.config.iframeCss;this.fileDialogsLoaded=this.bbCode||this.config.useFileDialogs===false;if(this.config.bbCodeTags&&this.bbCode){this.BBCODE_TAGS=this.config.bbCodeTags}if(this.config.minBodyWidth)this.MIN_WIDTH=parseInt(this.config.minBodyWidth);if(this.config.minBodyHeight)this.MIN_HEIGHT=parseInt(this.config.minBodyHeight);if(this.config.normalBodyWidth)this.NORMAL_WIDTH=parseInt(this.config.normalBodyWidth);this.normalWidth=this.NORMAL_WIDTH;if(this.config.smiles){this.smilesIndex={};this.sortedSmiles=[];var e,i,s,n;for(e=0;e<this.config.smiles.length;e++){i=this.config.smiles[e];if(!i["codes"]||i["codes"]==i["code"]){this.smilesIndex[this.config.smiles[e].code]=i;this.sortedSmiles.push(i)}else if(i["codes"].length>0){n=i["codes"].split(" ");for(s=0;s<n.length;s++){this.smilesIndex[n[s]]=i;this.sortedSmiles.push({name:i.name,path:i.path,code:n[s]})}}}this.sortedSmiles=this.sortedSmiles.sort(function(t,e){return e.code.length-t.code.length})}this.allowPhp=!!this.config.allowPhp;this.lpa=!this.config.allowPhp&&this.config.limitPhpAccess;this.templateId=this.config.templateId;this.componentFilter=this.config.componentFilter;this.showSnippets=this.config.showSnippets!==false;this.showComponents=this.config.showComponents!==false&&(this.allowPhp||this.lpa);this.showTaskbars=this.config.showTaskbars!==false&&(this.showSnippets||this.showComponents);this.templates={};this.templates[this.templateId]=this.config.templateParams},InitEventHandlers:function(){var e=this;BX.bind(this.dom.cont,"click",BX.proxy(this.OnClick,this));BX.bind(this.dom.cont,"mousedown",BX.proxy(this.OnMousedown,this));BX.bind(t,"resize",function(){e.ResizeSceleton()});if(BX.adminMenu){BX.addCustomEvent(BX.adminMenu,"onAdminMenuResize",function(){e.ResizeSceleton()})}BX.addCustomEvent(this,"OnIframeFocus",function(){e.bookmark=null;if(e.statusInterval){clearInterval(e.statusInterval)}e.statusInterval=setInterval(BX.proxy(e.CheckCurrentStatus,e),500)});BX.addCustomEvent(this,"OnIframeBlur",function(){e.bookmark=null;if(e.statusInterval){clearInterval(e.statusInterval)}});BX.addCustomEvent(this,"OnTextareaFocus",function(){e.bookmark=null;if(e.statusInterval){clearInterval(e.statusInterval)}});BX.addCustomEvent(this,"OnSurrogateDblClick",function(t,i,s,n){if(i){switch(i.tag){case"php":case"javascript":case"htmlcomment":case"iframe":case"style":e.GetDialog("Source").Show(i);break}}});if(this.dom.form){BX.bind(this.dom.form,"submit",BX.proxy(this.OnSubmit,this));setTimeout(function(){if(e.dom.form.BXAUTOSAVE){e.InitAutosaveHandlers()}},100)}BX.addCustomEvent(this,"OnSpecialcharInserted",function(t){var i=e.GetLastSpecialchars(),s=BX.util.array_search(t,i);if(s!==-1){i=BX.util.deleteFromArray(i,s);i.unshift(t)}else{i.unshift(t);i.pop()}e.config.lastSpecialchars=i;e.SaveOption("specialchars",i.join("|"))});this.parentDialog=BX.WindowManager.Get();if(this.parentDialog&&this.parentDialog.DIV&&BX.isNodeInDom(this.parentDialog.DIV)&&BX.findParent(this.dom.cont,function(t){return t==e.parentDialog.DIV})){BX.addCustomEvent(this.parentDialog,"onWindowResizeExt",function(){e.ResizeSceleton()})}if(this.config.autoResize){BX.addCustomEvent(this,"OnIframeKeyup",BX.proxy(this.AutoResizeSceleton,this));BX.addCustomEvent(this,"OnInsertHtml",BX.proxy(this.AutoResizeSceleton,this));BX.addCustomEvent(this,"OnIframeSetValue",BX.proxy(this.AutoResizeSceleton,this));BX.addCustomEvent(this,"OnFocus",BX.proxy(this.AutoResizeSceleton,this))}BX.addCustomEvent(this,"OnIframeKeyup",BX.proxy(this.CheckBodyHeight,this))},BuildSceleton:function(){this.dom.cont=BX("bx-html-editor-"+this.id);this.dom.toolbarCont=BX("bx-html-editor-tlbr-cnt-"+this.id);this.dom.toolbar=BX("bx-html-editor-tlbr-"+this.id);this.dom.areaCont=BX("bx-html-editor-area-cnt-"+this.id);this.dom.iframeCont=BX("bx-html-editor-iframe-cnt-"+this.id);this.dom.textareaCont=BX("bx-html-editor-ta-cnt-"+this.id);this.dom.resizerOverlay=BX("bx-html-editor-res-over-"+this.id);this.dom.splitResizer=BX("bx-html-editor-split-resizer-"+this.id);this.dom.splitResizer.className=this.config.splitVertical?"bxhtmled-split-resizer-ver":"bxhtmled-split-resizer-hor";BX.bind(this.dom.splitResizer,"mousedown",BX.proxy(this.StartSplitResize,this));this.dom.taskbarCont=BX("bx-html-editor-tskbr-cnt-"+this.id);this.dom.navCont=BX("bx-html-editor-nav-cnt-"+this.id);this.dom.fileDialogsWrap=BX("bx-html-editor-file-dialogs-"+this.id)},ResizeSceleton:function(t,e,i){var s=this;if(this.expanded){var n=BX.GetWindowInnerSize(document);t=this.config.width=n.innerWidth;e=this.config.height=n.innerHeight}if(!t){t=this.config.width}if(!e){e=this.config.height}this.dom.cont.style.minWidth=this.MIN_WIDTH+"px";this.dom.cont.style.minHeight=this.MIN_HEIGHT+"px";var o,r;if(this.resizeTimeout){clearTimeout(this.resizeTimeout);this.resizeTimeout=null}if(t.toString().indexOf("%")!==-1){o=t;t=this.dom.cont.offsetWidth;if(!t){this.resizeTimeout=setTimeout(function(){s.ResizeSceleton(t,e,i)},500);return}}else{if(t<this.MIN_WIDTH){t=this.MIN_WIDTH}o=t+"px"}this.dom.cont.style.width=o;this.dom.toolbarCont.style.width=o;if(e.toString().indexOf("%")!==-1){r=e;e=this.dom.cont.offsetHeight}else{if(e<this.MIN_HEIGHT){e=this.MIN_HEIGHT}r=e+"px"}this.dom.cont.style.height=r;var a=Math.max(t,this.MIN_WIDTH),h=Math.max(e,this.MIN_HEIGHT),l=this.toolbar.GetHeight(),d=this.showTaskbars?this.taskbarManager.GetWidth(true,a*.8):0,c=h-l-(this.config.showNodeNavi&&this.nodeNavi?this.nodeNavi.GetHeight():0),u=a-d;this.dom.areaCont.style.top=l?l+"px":0;this.SetAreaContSize(u,c,i);this.dom.taskbarCont.style.height=c+"px";this.dom.taskbarCont.style.width=d+"px";if(this.showTaskbars){this.taskbarManager.Resize(d,c)}this.toolbar.AdaptControls(t)},CheckBodyHeight:function(){if(this.iframeView.IsShown()){var t=8,e,i=this.GetIframeDoc();if(i&&i.body){e=i.body.parentNode.offsetHeight-t*2;if(e<=20){setTimeout(BX.proxy(this.CheckBodyHeight,this),300)}else if(this.config.autoResize||e>i.body.offsetHeight){i.body.style.minHeight=e+"px"}}}},GetSceletonSize:function(){return{width:this.dom.cont.offsetWidth,height:this.dom.cont.offsetHeight}},AutoResizeSceleton:function(){if(this.expanded||!this.IsShown()||this.iframeView.IsEmpty())return;var t=parseInt(this.config.autoResizeMaxHeight||0),e=parseInt(this.config.autoResizeMinHeight||50),i=this.dom.areaCont.offsetHeight,s,n=this;if(this.autoResizeTimeout){clearTimeout(this.autoResizeTimeout)}this.autoResizeTimeout=setTimeout(function(){s=n.GetHeightByContent();if(s>i){if(BX.browser.IsIOS()){t=Infinity}else if(!t||t<10){t=Math.round(BX.GetWindowInnerSize().innerHeight*.9)}s=Math.min(s,t);s=Math.max(s,e);n.SmoothResizeSceleton(s)}},300)},GetHeightByContent:function(){var t=parseInt(this.config.autoResizeOffset||80),e;if(this.GetViewMode()=="wysiwyg"){var i=this.GetIframeDoc().body,s=i.lastChild,n=false;e=i.offsetHeight;while(true){if(!s){break}if(s.offsetTop){n=s.offsetTop+(s.offsetHeight||0);e=n+t;break}else{s=s.previousSibling}}var o=BX.GetWindowSize(this.GetIframeDoc());if(o.scrollHeight-o.innerHeight>5){e=Math.max(o.scrollHeight+t,e)}}else{e=(this.textareaView.element.value.split("\n").length+5)*17}return e},SmoothResizeSceleton:function(t){var e=this,i=this.GetSceletonSize(),s=i.height,n=0,o=t>s,r=50,a=5;if(!o)return;if(this.smoothResizeInt){clearInterval(this.smoothResizeInt)}this.smoothResizeInt=setInterval(function(){s+=Math.round(a*n);if(s>t){clearInterval(e.smoothResizeInt);if(s>t){s=t}}e.config.height=s;e.ResizeSceleton();n++},r)},SetAreaContSize:function(t,e,i){t+=2;this.dom.areaCont.style.width=t+"px";this.dom.areaCont.style.height=e+"px";if(i&&i.areaContTop){this.dom.areaCont.style.top=i.areaContTop+"px"}var s=3;if(this.currentViewName=="split"){function n(t,e,i){if(t<e){t=e}if(t>i){t=i}return t}var o=10,r,a,h;if(this.config.splitVertical==true){r=i&&i.deltaX?i.deltaX:0;a=n(t*this.config.splitRatio/(1+this.config.splitRatio)-r,o,t-o);h=t-a;this.dom.iframeCont.style.width=a-s+"px";this.dom.iframeCont.style.height=e+"px";this.dom.iframeCont.style.top=0;this.dom.iframeCont.style.left=0;this.dom.textareaCont.style.width=h-s+"px";this.dom.textareaCont.style.height=e+"px";this.dom.textareaCont.style.top=0;this.dom.textareaCont.style.left=a+"px";this.dom.splitResizer.className="bxhtmled-split-resizer-ver";this.dom.splitResizer.style.top=0;this.dom.splitResizer.style.left=a-3+"px";this.dom.textareaCont.style.height=e+"px"}else{r=i&&i.deltaY?i.deltaY:0;a=n(e*this.config.splitRatio/(1+this.config.splitRatio)-r,o,e-o);h=e-a;this.dom.iframeCont.style.width=t-s+"px";this.dom.iframeCont.style.height=a+"px";this.dom.iframeCont.style.top=0;this.dom.iframeCont.style.left=0;this.dom.textareaCont.style.width=t-s+"px";this.dom.textareaCont.style.height=h+"px";this.dom.textareaCont.style.top=a+"px";this.dom.textareaCont.style.left=0;this.dom.splitResizer.className="bxhtmled-split-resizer-hor";this.dom.splitResizer.style.top=a-3+"px";this.dom.splitResizer.style.left=0}if(i&&i.updateSplitRatio){this.config.splitRatio=a/h;this.SaveOption("split_ratio",this.config.splitRatio)}}else{this.dom.iframeCont.style.width=t-s+"px";this.dom.iframeCont.style.height=e+"px";this.dom.iframeCont.style.top=0;this.dom.iframeCont.style.left=0;this.dom.textareaCont.style.width=t-s+"px";this.dom.textareaCont.style.height=e+"px";this.dom.textareaCont.style.top=0;this.dom.textareaCont.style.left=0}},BuildToolbar:function(){this.toolbar=new BXHtmlEditor.Toolbar(this,this.GetTopControls())},GetTopControls:function(){this.On("GetTopButtons",[t.BXHtmlEditor.Controls]);return t.BXHtmlEditor.Controls},CreateIframeSandBox:function(){return new s(BX.proxy(this.OnCreateIframe,this),{editor:this,cont:this.dom.iframeCont})},OnCreateIframe:function(){this.On("OnCreateIframeBefore");this.iframeView.OnCreateIframe();this.selection=new n(this);this.action=new BXEditorActions(this);this.config.content=this.dom.pValueInput.value;this.SetContent(this.config.content,true);this.undoManager=new a(this);this.action.Exec("styleWithCSS",false,true);this.iframeView.InitAutoLinking();this.SetView(this.config.view,false);if(this.config.setFocusAfterShow!==false){this.Focus(false)}this.sandbox.inited=true;this.On("OnCreateIframeAfter",[this])},GetDialog:function(e,i){if(!this.dialogs[e]&&t.BXHtmlEditor.dialogs[e])this.dialogs[e]=new t.BXHtmlEditor.dialogs[e](this,i);return this.dialogs[e]||null},Show:function(){this.dom.cont.style.display=""},Hide:function(){this.dom.cont.style.display="none"},IsShown:function(){return this.inited&&this.dom.cont.style.display!=="none"&&this.dom.cont.offsetWidth>0&&BX.isNodeInDom(this.dom.cont)},SetView:function(t,e){this.On("OnSetViewBefore");if(t=="split"&&this.bbCode)t="wysiwyg";if(this.currentViewName!=t){if(t=="wysiwyg"){this.iframeView.Show();this.textareaView.Hide();this.dom.splitResizer.style.display="none";this.CheckBodyHeight()}else if(t=="code"){this.iframeView.Hide();this.textareaView.Show();this.CheckCurrentStatus(false);this.dom.splitResizer.style.display="none"}else if(t=="split"){this.textareaView.Show();this.iframeView.Show();this.dom.splitResizer.style.display="";this.CheckBodyHeight()}this.currentViewName=t}if(e!==false){this.SaveOption("view",t)}this.ResizeSceleton();this.On("OnSetViewAfter")},GetViewMode:function(){return this.currentViewName},SetContent:function(t,e){this.On("OnSetContentBefore");if(this.bbCode){var i=this.bbParser.Parse(t);this.iframeView.SetValue(i,e)}else{this.iframeView.SetValue(t,e)}this.textareaView.SetValue(t,false);this.On("OnSetContentAfter")},Focus:function(t){if(this.currentViewName=="wysiwyg"){this.iframeView.Focus(t)}else if(this.currentViewName=="code"){this.textareaView.Focus(t)}else if(this.currentViewName=="split"){if(this.synchro.GetSplitMode()=="wysiwyg"){this.iframeView.Focus(t)}else{this.textareaView.Focus(t)}}this.On("OnFocus");return this},SaveContent:function(){if(this.currentViewName=="wysiwyg"||this.currentViewName=="split"&&this.synchro.GetSplitMode()=="wysiwyg"){this.synchro.lastIframeValue="";this.synchro.FromIframeToTextarea(true,true)}else{this.textareaView.SaveValue()}},GetContent:function(){this.SaveContent();return this.textareaView.GetValue()},IsExpanded:function(){return this.expanded},Expand:function(e){if(e==undefined){e=!this.expanded}var i=this,s=BX.GetWindowInnerSize(document),n,o,r,a,h,l,d,c;if(e){var u=BX.GetWindowScrollPos(document),f=BX.pos(this.dom.cont);n=this.dom.cont.offsetWidth;o=this.dom.cont.offsetHeight;r=f.top;a=f.left;h=s.innerWidth;l=s.innerHeight;d=u.scrollTop;c=u.scrollLeft;this.savedSize={width:n,height:o,top:r,left:a,scrollLeft:u.scrollLeft,scrollTop:u.scrollTop,configWidth:this.config.width,configHeight:this.config.height};this.config.width=h;this.config.height=l;BX.addClass(this.dom.cont,"bx-html-editor-absolute");this._bodyOverflow=document.body.style.overflow;document.body.style.overflow="hidden";this.dummieDiv=BX.create("DIV");this.dummieDiv.style.width=n+"px";this.dummieDiv.style.height=o+"px";this.dom.cont.parentNode.insertBefore(this.dummieDiv,this.dom.cont);document.body.appendChild(this.dom.cont);BX.addCustomEvent(this,"OnIframeKeydown",BX.proxy(this.CheckEscCollapse,this));BX.bind(document.body,"keydown",BX.proxy(this.CheckEscCollapse,this));BX.bind(t,"scroll",BX.proxy(this.PreventScroll,this))}else{n=this.dom.cont.offsetWidth;o=this.dom.cont.offsetHeight;r=this.savedSize.scrollTop;a=this.savedSize.scrollLeft;h=this.savedSize.width;l=this.savedSize.height;d=this.savedSize.top;c=this.savedSize.left;BX.removeCustomEvent(this,"OnIframeKeydown",BX.proxy(this.CheckEscCollapse,this));BX.unbind(document.body,"keydown",BX.proxy(this.CheckEscCollapse,this));BX.unbind(t,"scroll",BX.proxy(this.PreventScroll,this))}this.dom.cont.style.width=n+"px";this.dom.cont.style.height=o+"px";this.dom.cont.style.top=r+"px";this.dom.cont.style.left=a+"px";var m=this.GetContent();this.expandAnimation=new BX.easing({duration:300,start:{height:o,width:n,top:r,left:a},finish:{height:l,width:h,top:d,left:c},transition:BX.easing.makeEaseOut(BX.easing.transitions.quart),step:function(t){i.dom.cont.style.width=t.width+"px";i.dom.cont.style.height=t.height+"px";i.dom.cont.style.top=t.top+"px";i.dom.cont.style.left=t.left+"px";i.ResizeSceleton(t.width.toString(),t.height.toString())},complete:function(){i.expandAnimation=null;if(!e){i.util.ReplaceNode(i.dummieDiv,i.dom.cont);i.dummieDiv=null;i.dom.cont.style.width="";i.dom.cont.style.height="";i.dom.cont.style.top="";i.dom.cont.style.left="";BX.removeClass(i.dom.cont,"bx-html-editor-absolute");document.body.style.overflow=i._bodyOverflow;i.config.width=i.savedSize.configWidth;i.config.height=i.savedSize.configHeight;i.ResizeSceleton()}setTimeout(function(){i.CheckAndReInit(m)},10)}});this.expandAnimation.animate();this.expanded=e},CheckEscCollapse:function(t,e,i,s){if(!e){e=t.keyCode}if(this.IsExpanded()&&e==this.KEY_CODES["escape"]&&!this.IsPopupsOpened()){this.Expand(false);return BX.PreventDefault(t)}},PreventScroll:function(e){t.scrollTo(this.savedSize.scrollLeft,this.savedSize.scrollTop);return BX.PreventDefault(e)},IsPopupsOpened:function(){return!!(this.dialogShown||this.popupShown||this.contextMenuShown||this.overlay.bShown)},ReInitIframe:function(){this.sandbox.InitIframe();this.iframeView.OnCreateIframe();this.synchro.StopSync();this.synchro.lastTextareaValue="";this.synchro.FromTextareaToIframe(true);this.synchro.StartSync();this.iframeView.ReInit();this.Focus()},CheckAndReInit:function(t){if(this.sandbox.inited){var e=this.sandbox.GetWindow();if(e){var i=this.sandbox.GetDocument();if(i!==this.iframeView.document||!i.head||i.head.innerHTML==""){this.iframeView.document=i;this.iframeView.element=i.body;this.ReInitIframe()}else if(i.body){i.body.style.minHeight=""}}else{throw new Error("HtmlEditor: CheckAndReInit error iframe isn't in the DOM")}}if(t!==undefined){this.SetContent(t,true);this.Focus(true)}},Disable:function(){},Enable:function(){},CheckConfig:function(t){if(t.content===undefined){t.content=""}return t},GetInnerHtml:function(t){var e="%7E",i="&amp;",s=t.innerHTML;if(s.indexOf(i)!==-1||s.indexOf(e)!==-1){s=s.replace(/(?:href|src)\s*=\s*("|')([\s\S]*?)(\1)/gi,function(t){t=t.replace(/%7E/gi,"~");t=t.replace(/&amp;/gi,"&");return t})}s=s.replace(/(?:title|alt)\s*=\s*("|')([\s\S]*?)(\1)/gi,function(t){t=t.replace(/</g,"&lt;");t=t.replace(/>/g,"&gt;");return t});if(this.bbCode){s=s.replace(/[\s\n\r]*?<!--[\s\S]*?-->[\s\n\r]*?/gi,"")}return s},InitUtil:function(){var e=this;this.util={};if("textContent"in document.documentElement){this.util.SetTextContent=function(t,e){t.textContent=e};this.util.GetTextContent=function(t){return t.textContent}}else if("innerText"in document.documentElement){this.util.SetTextContent=function(t,e){t.innerText=e};this.util.GetTextContent=function(t){return t.innerText}}else{this.util.SetTextContent=function(t,e){t.nodeValue=e};this.util.GetTextContent=function(t){return t.nodeValue}}this.util.AutoCloseTagSupported=function(){var t=document.createElement("div"),i,s;t.innerHTML="<p><div></div>";s=t.innerHTML.toLowerCase();i=s==="<p></p><div></div>"||s==="<p><div></div></p>";e.util.AutoCloseTagSupported=function(){return i};return i};this.util.FirstLetterSupported=function(){var t=BX.browser.IsChrome()||BX.browser.IsSafari();e.util.FirstLetterSupported=function(){return t};return t};this.util.CheckGetAttributeTruth=function(){var t=document.createElement("td"),i=t.getAttribute("rowspan")!="1";e.util.CheckGetAttributeTruth=function(){return i};return i};this.util.CheckHTML5Support=function(t){if(!t){t=document}var i=false,s="<article>bitrix</article>",n=t.createElement("div");n.innerHTML=s;i=n.innerHTML.toLowerCase()===s;e.util.CheckHTML5Support=function(){return i};return i};this.util.CheckHTML5FullSupport=function(t){if(!t){t=document}var i,s=e.GetHTML5Tags(),n=false,o=t.createElement("div");for(var r=0;r<s.length;r++){i="<"+s[r]+">bitrix</"+s[r]+">";o.innerHTML=i;n=o.innerHTML.toLowerCase()===i;if(!n){break}}e.util.CheckHTML5FullSupport=function(){return n};return n};this.util.GetEmptyImage=function(){return e.EMPTY_IMAGE_SRC};this.util.CheckDataTransferSupport=function(){var i=false;try{i=!!(t.Clipboard||t.DataTransfer).prototype.getData}catch(s){}e.util.CheckDataTransferSupport=function(){return i};return i};this.util.CheckImageSelectSupport=function(){var t=!(BX.browser.IsChrome()||BX.browser.IsSafari());e.util.CheckImageSelectSupport=function(){return t};return t};this.util.CheckPreCursorSupport=function(){var t=!(BX.browser.IsIE()||BX.browser.IsIE10()||BX.browser.IsIE11());e.util.CheckPreCursorSupport=function(){return t};return t};this.util.Refresh=function(t){if(t&&t.parentNode){var i="bx-editor-refresh";BX.addClass(t,i);BX.removeClass(t,i);if(BX.browser.IsFirefox()){try{var s,n=t.ownerDocument,o=n.getElementsByTagName("I"),r=o.length;for(s=0;s<o.length;s++){o[s].setAttribute("data-bx-orgig-i",true)}n.execCommand("italic",false,null);n.execCommand("italic",false,null);var a=n.getElementsByTagName("I");if(a.length!==r){for(s=0;s<a.length;s++){if(a[s].getAttribute("data-bx-orgig-i")){a[s].removeAttribute("data-bx-orgig-i")}else{e.util.ReplaceWithOwnChildren(a[s])}}}}catch(h){}}}};this.util.addslashes=function(t){t=t.replace(/\\/g,"\\\\");t=t.replace(/"/g,'\\"');return t};this.util.stripslashes=function(t){t=t.replace(/\\"/g,'"');t=t.replace(/\\\\/g,"\\");return t};this.util.ReplaceNode=function(t,e){t.parentNode.insertBefore(e,t);t.parentNode.removeChild(t);return e};this.util.DocumentHasTag=function(t,i){var s={},n=e.id+":"+i,o=s[n];if(!o)o=s[n]=t.getElementsByTagName(i);return o.length>0};this.util.IsSplitPoint=function(t,e){var i=e>0&&e<t.childNodes.length;if(rangy.dom.isCharacterDataNode(t)){if(e==0)i=!!t.previousSibling;else if(e==t.length)i=!!t.nextSibling;else i=true}return i};this.util.SplitNodeAt=function(t,i,s){var n;if(rangy.dom.isCharacterDataNode(i)){if(s==0){s=rangy.dom.getNodeIndex(i);i=i.parentNode}else if(s==i.length){s=rangy.dom.getNodeIndex(i)+1;i=i.parentNode}else{n=rangy.dom.splitDataNode(i,s)}}if(!n){n=i.cloneNode(false);if(n.id){n.removeAttribute("id")}var o;while(o=i.childNodes[s]){n.appendChild(o)}rangy.dom.insertAfter(n,i)}if(i&&i.nodeName=="BODY"){return n}return i==t?n:e.util.SplitNodeAt(t,n.parentNode,rangy.dom.getNodeIndex(n))};this.util.ReplaceWithOwnChildren=function(t){var e=t.parentNode;while(t.firstChild){e.insertBefore(t.firstChild,t)}e.removeChild(t)};this.util.IsBlockElement=function(t){var e=BX.style(t,"display");return e&&e.toLowerCase()==="block"};this.util.IsBlockNode=function(t){return t&&t.nodeType==1&&BX.util.in_array(t.nodeName,e.GetBlockTags())};this.util.CopyAttributes=function(t,e,i){if(e&&i){var s,n,o=t.length;for(n=0;n<o;n++){s=t[n];if(e[s])i[s]=e[s]}}};this.util.RenameNode=function(t,i){var s=t.ownerDocument.createElement(i),n;while(n=t.firstChild)s.appendChild(n);e.util.CopyAttributes(["align","className"],t,s);if(t.style.cssText!=""){s.style.cssText=t.style.cssText}t.parentNode.replaceChild(s,t);return s};this.util.GetInvisibleTextNode=function(){return e.iframeView.document.createTextNode(e.INVISIBLE_SPACE)};this.util.IsEmptyNode=function(t,i,s){var n;if(t.nodeType==3){n=t.data===""||t.data===e.INVISIBLE_SPACE||t.data==="\n"&&i;if(!n&&s&&t.data.toString().match(/^[\s\n\r\t]+$/gi)){n=true}}else if(t.nodeType==1){n=t.innerHTML===""||t.innerHTML===e.INVISIBLE_SPACE;if(!n&&s&&t.innerHTML.toString().match(/^[\s\n\r\t]+$/gi)){n=true}}return n};var i=document.documentElement;if("textContent"in i){this.util.SetTextContent=function(t,e){t.textContent=e};this.util.GetTextContent=function(t){return t.textContent}}else if("innerText"in i){this.util.SetTextContent=function(t,e){t.innerText=e};this.util.GetTextContent=function(t){return t.innerText}}else{this.util.SetTextContent=function(t,e){t.nodeValue=e};this.util.GetTextContent=function(t){return t.nodeValue}}this.util.GetTextContentEx=function(t){var i,s=t.cloneNode(true),n=s.getElementsByTagName("SCRIPT");for(i=n.length-1;i>=0;i--){BX.remove(n[i])}return e.util.GetTextContent(s)};this.util.RgbToHex=function(t){if(!t)t="";if(t.search("rgb")!==-1){function e(t){return("0"+parseInt(t).toString(16)).slice(-2)}t=t.replace(/rgba\(0,\s*0,\s*0,\s*0\)/gi,"transparent");t=t.replace(/rgba?\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3})(?:,\s*([\d\.]{1,3}))?\)/gi,function(t,i,s,n,o){return"#"+e(i)+e(s)+e(n)})}return t};this.util.CheckCss=function(t,e,i){var s=true,n;for(n in e){if(e.hasOwnProperty(n)){if(t.style[n]!=""){s=s&&(i?t.style[n]==e[n]:true)}else{s=false}}}return s};this.util.SetCss=function(t,e){if(t&&e&&typeof e=="object"){for(var i in e){if(e.hasOwnProperty(i)){t.style[i]=e[i]}}}};this.util.InsertAfter=function(t,e){return rangy.dom.insertAfter(t,e)};this.util.GetNodeDomOffset=function(t){var e=0;while(t.parentNode&&t.parentNode.nodeName!=="BODY"){t=t.parentNode;e++}return e};this.util.CheckSurrogateNode=function(t){return e.phpParser.CheckParentSurrogate(t)};this.util.CheckSurrogateDd=function(t){return e.phpParser.CheckSurrogateDd(t)};this.util.GetPreviousNotEmptySibling=function(t){var i=t.previousSibling;while(i&&i.nodeType==3&&e.util.IsEmptyNode(i,true,true)){i=i.previousSibling}return i};this.util.GetNextNotEmptySibling=function(t){var i=t.nextSibling;while(i&&i.nodeType==3&&e.util.IsEmptyNode(i,true,true)){i=i.nextSibling}return i};this.util.IsEmptyLi=function(t){if(t&&t.nodeName=="LI"){return e.util.IsEmptyNode(t,true,true)||t.innerHTML.toLowerCase()=="<br>"}return false};this.util.FindParentEx=function(t,e,i){if(BX.checkNode&&BX.checkNode(t,e))return t;return BX.findParent(t,e,i)}},Parse:function(t,e,i){e=!!e;this.On("OnParse",[e]);if(e){t=this.parser.Parse(t,this.GetParseRules(),this.GetIframeDoc(),true,e);if((i===true||this.textareaView.IsShown())&&!this.bbCode){t=this.FormatHtml(t)}t=this.phpParser.ParseBxNodes(t)}else{t=this.phpParser.ParsePhp(t);t=this.parser.Parse(t,this.GetParseRules(),this.GetIframeDoc(),true,e)}return t},On:function(t,e){BX.onCustomEvent(this,t,e||[])},GetIframeDoc:function(){if(!this.document){this.document=this.sandbox.GetDocument();BX.addCustomEvent(this,"OnIframeReInit",BX.proxy(function(){this.document=this.sandbox.GetDocument()},this))}return this.document},GetParseRules:function(){this.rules=e;this.On("OnGetParseRules");var t=this;this.GetParseRules=function(){return t.rules};return this.rules},GetHTML5Tags:function(){return this.HTML5_TAGS},GetBlockTags:function(){return this.BLOCK_TAGS},SetBxTag:function(t,e){var i;if(e.id||t&&t.id)i=e.id||t.id;if(!i){i="bxid"+Math.round(Math.random()*1e9)}else{if(this.bxTags[i]){if(!e.tag)e.tag=this.bxTags[i].tag}}e.id=i;if(t)t.id=e.id;this.bxTags[e.id]=e;return e.id},GetBxTag:function(t){var e;if(typeof t=="object"&&t&&t.id)e=t.id;else e=t;if(e){if(typeof e!="string"&&e.id)e=e.id;if(e&&e.length>0&&this.bxTags[e]&&this.bxTags[e].tag){this.bxTags[e].tag=this.bxTags[e].tag.toLowerCase();return this.bxTags[e]}}return{tag:false}},OnMousedown:function(t){var e=t.target||t.srcElement;if(e&&(e.getAttribute||e.parentNode)){var i=this,s=e.getAttribute("data-bx-type");if(!s){e=BX.findParent(e,function(t){return t==i.dom.cont||t.getAttribute&&t.getAttribute("data-bx-type")},this.dom.cont);s=e&&e.getAttribute?e.getAttribute("data-bx-type"):null}if(s=="action"){return BX.PreventDefault(t)}}return true},OnClick:function(t){var e=t.target||t.srcElement,i=e&&e.getAttribute?e.getAttribute("data-bx-type"):false;this.On("OnClickBefore",[{e:t,target:e,bxType:i}]);this.CheckCommand(e)},CheckCommand:function(t){if(t&&(t.getAttribute||t.parentNode)){var e=this,i=t.getAttribute("data-bx-type");

if(!i){t=BX.findParent(t,function(t){return t==e.dom.cont||t.getAttribute&&t.getAttribute("data-bx-type")},this.dom.cont);i=t&&t.getAttribute?t.getAttribute("data-bx-type"):null}if(i=="action"){var s=t.getAttribute("data-bx-action"),n=t.getAttribute("data-bx-value");if(this.action.IsSupported(s)){this.action.Exec(s,n)}}}},SetSplitMode:function(t,e){this.config.splitVertical=!!t;if(e!==false){this.SaveOption("split_vertical",this.config.splitVertical?1:0)}this.SetView("split",e)},GetSplitMode:function(){return this.config.splitVertical},StartSplitResize:function(t){this.dom.resizerOverlay.style.display="block";var e=0,i=0,s=BX.GetWindowScrollPos(),n=t.clientX+s.scrollLeft,o=t.clientY+s.scrollTop,r=this;function a(t,a){var h=t.clientX+s.scrollLeft,l=t.clientY+s.scrollTop;if(n==h&&o==l){return}e=n-h;i=o-l;r.ResizeSceleton(0,0,{deltaX:e,deltaY:i,updateSplitRatio:a})}function h(t){a(t,true);BX.unbind(document,"mousemove",a);BX.unbind(document,"mouseup",h);r.dom.resizerOverlay.style.display="none"}BX.bind(document,"mousemove",a);BX.bind(document,"mouseup",h)},Request:function(t){if(!t.url)t.url=this.config.actionUrl;if(t.bIter!==false)t.bIter=true;if(!t.postData&&!t.getData)t.getData=this.GetReqData();var e=t.getData?t.getData.reqId:t.postData.reqId;var i=this,s=0;var n=function(n){function o(){var r=t.handler(i.GetRequestRes(e),n);if(r===false&&++s<20&&t.bIter)setTimeout(o,5);else i.ClearRequestRes(e)}setTimeout(o,50)};if(t.postData)BX.ajax.post(t.url,t.postData,n);else BX.ajax.get(t.url,t.getData,n)},GetRequestRes:function(t){if(top.BXHtmlEditorAjaxResponse[t]!=undefined)return top.BXHtmlEditorAjaxResponse[t];return{}},ClearRequestRes:function(t){if(top.BXHtmlEditorAjaxResponse){top.BXHtmlEditorAjaxResponse[t]=null;delete top.BXHtmlEditorAjaxResponse[t]}},GetReqData:function(t,e){if(!e)e={};if(t)e.action=t;e.sessid=BX.bitrix_sessid();e.bx_html_editor_request="Y";e.reqId=Math.round(Math.random()*1e6);return e},GetTemplateId:function(){return this.templateId},GetComponentFilter:function(){return this.componentFilter},GetTemplateParams:function(){return this.templates[this.templateId]},GetTemplateStyles:function(){var t=this.templates[this.templateId]||{};return t.STYLES||""},ApplyTemplate:function(t){if(this.templateId!==t){if(this.templates[t]){this.templateId=t;var e=this.templates[t],i,s=this.sandbox.GetDocument(),n=s.head||s.getElementsByTagName("HEAD")[0],o=n.getElementsByTagName("STYLE"),r=n.getElementsByTagName("LINK");for(i=0;i<o.length;i++){if(o[i].getAttribute("data-bx-template-style")=="Y")BX.cleanNode(o[i],true)}i=0;while(i<r.length){if(r[i].getAttribute("data-bx-template-style")=="Y"){BX.remove(r[i],true)}else{i++}}if(e["STYLES"]){n.appendChild(BX.create("STYLE",{props:{type:"text/css"},text:e["STYLES"]},s)).setAttribute("data-bx-template-style","Y")}if(e&&e["EDITOR_STYLES"]){for(i=0;i<e["EDITOR_STYLES"].length;i++){n.appendChild(BX.create("link",{props:{rel:"stylesheet",href:e["EDITOR_STYLES"][i]+"_"+this.cssCounter++}},s)).setAttribute("data-bx-template-style","Y")}}this.On("OnApplySiteTemplate",[t])}else{var a=this;this.Request({getData:this.GetReqData("load_site_template",{site_template:t}),handler:function(e){a.templates[t]=e;a.ApplyTemplate(t)}})}}},FormatHtml:function(e,i){if(e.length<this.MAX_HANDLED_FORMAT_LENGTH||i===true){if(!this.formatter)this.formatter=new t.BXHtmlEditor.BXCodeFormatter(this);var s=(new Date).getTime();e=this.formatter.Format(e);var n=(new Date).getTime();if(n-s>this.MAX_HANDLED_FORMAT_TIME)this.MAX_HANDLED_FORMAT_LENGTH-=5e3}return e},GetFontFamilyList:function(){if(!this.fontFamilyList){this.fontFamilyList=[{value:["Times New Roman","Times"],name:"Times New Roman"},{value:["Courier New"],name:"Courier New"},{value:["Arial","Helvetica"],name:"Arial / Helvetica"},{value:["Arial Black","Gadget"],name:"Arial Black"},{value:["Tahoma","Geneva"],name:"Tahoma / Geneva"},{value:"Verdana",name:"Verdana"},{value:["Georgia","serif"],name:"Georgia"},{value:"monospace",name:"monospace"}];this.On("GetFontFamilyList",[this.fontFamilyList])}return this.fontFamilyList},CheckCurrentStatus:function(t){var e,i,s,n,o=this.GetActiveActions();if(t===false){for(i in o){if(o.hasOwnProperty(i)&&this.action.IsSupported(i)){e=o[i];e.control.SetValue(false,null,i)}}}if(!this.iframeView.IsFocused())return this.On("OnIframeBlur");var r=this.selection.GetRange();if(!r||!r.isValid())return this.On("OnIframeBlur");for(i in o){if(o.hasOwnProperty(i)&&this.action.IsSupported(i)){e=o[i];s=this.action.CheckState(i,e.value);n=e.control.GetValue();if(s){e.control.SetValue(true,s,i)}else{e.control.SetValue(false,null,i)}}}},RegisterCheckableAction:function(t,e){if(!this.checkedActionList)this.checkedActionList={};this.checkedActionList[t]=e},GetActiveActions:function(){return this.checkedActionList},SaveOption:function(t,e){BX.userOptions.save("html_editor",this.config.settingsKey,t,e)},GetCurrentCssClasses:function(t){return this.styles.GetCSS(this.templateId,this.templates[this.templateId].STYLES,this.templates[this.templateId].PATH||"",t||false)},GetStylesDescription:function(t){if(!t)t=this.templateId;var e={};if(t&&this.templates[t]){e=this.templates[t].STYLES_TITLE||{}}return e},IsInited:function(){return!!this.inited},IsContentChanged:function(){var t=this.config.content.replace(/[\s\n\r\t]+/gi,""),e=this.GetContent().replace(/[\s\n\r\t]+/gi,"");return t!=e},IsSubmited:function(){return this.isSubmited},OnSubmit:function(){if(!this.isSubmited&&this.dom.cont.style.display!=="none"){this.RemoveCursorNode();this.isSubmited=true;if(this.iframeView.IsFocused())this.On("OnIframeBlur");this.On("OnSubmit");this.SaveContent()}},AllowBeforeUnloadHandler:function(){this.beforeUnloadHandlerAllowed=true},DenyBeforeUnloadHandler:function(){this.beforeUnloadHandlerAllowed=false},Destroy:function(){this.sandbox.Destroy();BX.remove(this.dom.cont)},Check:function(){return this.dom.cont&&BX.isNodeInDom(this.dom.cont)},IsVisible:function(){return this.Check()&&this.dom.cont.offsetWidth>0},GetLastSpecialchars:function(){var t=["&cent;","&sect;","&euro;","&pound;","&yen;","&copy;","&reg;","&laquo;","&raquo;","&deg;","&plusmn;","&para;","&hellip;","&prime;","&Prime;","&trade;","&asymp;","&ne;","&lt;","&gt;"];if(this.config.lastSpecialchars&&typeof this.config.lastSpecialchars=="object"&&this.config.lastSpecialchars.length>1){return this.config.lastSpecialchars}else{return t}},GetIframeElement:function(t){var e=this.GetIframeDoc();return e?e.getElementById(t):null},RegisterDialog:function(e,i){t.BXHtmlEditor.dialogs[e]=i},SetConfigHeight:function(t){this.config.height=t;if(this.IsExpanded()){this.savedSize.configHeight=t;this.savedSize.height=t}},CheckBrowserCompatibility:function(){return!(BX.browser.IsOpera()||BX.browser.IsIE8()||BX.browser.IsIE7()||BX.browser.IsIE6()||!document.querySelectorAll)},GetCursorHtml:function(){return'<span id="bx-cursor-node"> </span>'},SetCursorNode:function(t){if(!t)t=this.selection.GetRange();this.RemoveCursorNode();this.selection.InsertHTML(this.GetCursorHtml(),t)},RestoreCursor:function(){var t=this.GetIframeElement("bx-cursor-node");if(t){this.selection.SetAfter(t);BX.remove(t)}},RemoveCursorNode:function(){if(this.synchro.IsFocusedOnTextarea()){}else{var t=this.GetIframeElement("bx-cursor-node");if(t){this.selection.SetAfter(t);BX.remove(t)}}},AddButton:function(e){if(e.compact==undefined)e.compact=false;if(e.toolbarSort==undefined)e.toolbarSort=301;if(e.hidden==undefined)e.hidden=false;var i=function(t,s){i.superclass.constructor.apply(this,arguments);this.id=e.id;this.title=e.name;if(e.iconClassName)this.className+=" "+e.iconClassName;if(e.action)this.action=e.action;if(e.disabledForTextarea!==undefined)this.disabledForTextarea=e.disabledForTextarea;this.Create();if(e.src)this.pCont.firstChild.style.background='url("'+e.src+'") no-repeat scroll 0 0';if(s)s.appendChild(this.GetCont())};BX.extend(i,t.BXHtmlEditor.Button);if(e.handler)i.prototype.OnClick=e.handler;t.BXHtmlEditor.Controls[e.id]=i;BX.addCustomEvent(this,"GetControlsMap",function(t){t.push({id:e.id,compact:e.compact,hidden:e.hidden,sort:e.toolbarSort,checkWidth:e.checkWidth==undefined?true:!!e.checkWidth,offsetWidth:e.offsetWidth||32})})},AddCustomParser:function(t){if(this.phpParser&&this.phpParser.AddCustomParser){this.phpParser.AddCustomParser(t)}else{var e=this;BX.addCustomEvent("OnEditorInitedAfter",function(){e.phpParser.AddCustomParser(t)})}},AddParser:function(t){if(t&&t.name&&typeof t.obj=="object"){this.parser.specialParsers[t.name]=t.obj}},InsertHtml:function(t,e){if(!this.synchro.IsFocusedOnTextarea()){this.Focus();if(!e)e=this.selection.GetRange();if(!e.collapsed&&e.startContainer==e.endContainer&&e.startContainer.nodeName!=="BODY"){var i=this.util.CheckSurrogateNode(e.startContainer);if(i){this.selection.SetAfter(i)}}this.selection.InsertHTML(t,e)}},ParseContentFromBbCode:function(t){if(this.bbCode){t=this.bbParser.Parse(t);t=this.Parse(t,true,true)}return t},LoadFileDialogs:function(t){var e=this;this.Request({getData:this.GetReqData("load_file_dialogs",{editor_id:this.id}),handler:function(i,s){s=BX.util.trim(s);e.dom.fileDialogsWrap.innerHTML=s;e.fileDialogsLoaded=true;setTimeout(t,100)}})},InitAutosaveHandlers:function(){var t=this,e=this.dom.form;try{BX.addCustomEvent(this,"OnSubmit",function(){e.BXAUTOSAVE.Init()});BX.addCustomEvent(this,"OnContentChanged",function(){e.BXAUTOSAVE.Init()});BX.addCustomEvent(e,"onAutoSave",function(e,i){if(t.IsShown()&&!t.IsSubmited()){i[t.config.inputName]=t.GetContent()}});BX.addCustomEvent(e,"onAutoSaveRestore",function(e,i){if(t.IsShown()){t.SetContent(i[t.config.inputName],true)}})}catch(i){}}};t.BXEditor=i;function s(t,e){this.callback=t||BX.DoNothing;this.config=e||{};this.editor=this.config.editor;this.iframe=this.CreateIframe();this.bSandbox=false;this.windowProperties=["parent","top","opener","frameElement","frames","localStorage","globalStorage","sessionStorage","indexedDB"];this.windowProperties2=["open","close","openDialog","showModalDialog","alert","confirm","prompt","openDatabase","postMessage","XMLHttpRequest","XDomainRequest"];this.documentProperties=["referrer","write","open","close"]}s.prototype={GetIframe:function(){return this.iframe},GetWindow:function(){this._readyError()},GetDocument:function(){this._readyError()},Destroy:function(){var t=this.GetIframe();t.parentNode.removeChild(t)},_readyError:function(){throw new Error("Sandbox: Sandbox iframe isn't loaded yet")},CreateIframe:function(){var t=this,e=BX.create("IFRAME",{props:{className:"bx-editor-iframe",frameborder:0,allowtransparency:"true",width:0,height:0,marginwidth:0,marginheight:0}});e.onload=function(){e.onreadystatechange=e.onload=null;t.OnLoadIframe(e)};e.onreadystatechange=function(){if(/loaded|complete/.test(e.readyState)){e.onreadystatechange=e.onload=null;t.OnLoadIframe(e)}};this.config.cont.appendChild(e);return e},OnLoadIframe:function(t){if(BX.isNodeInDom(t)){var e=this,i=t.contentWindow,s=i.document;this.InitIframe(t);i.onerror=function(t,e,i){throw new Error("Sandbox: "+t,e,i)};if(this.bSandbox){var n,o;for(n=0,o=this.windowProperties.length;n<o;n++){this._unset(i,this.windowProperties[n])}for(n=0,o=this.windowProperties2.length;n<o;n++){this._unset(i,this.windowProperties2[n],BX.DoNothing())}for(n=0,o=this.documentProperties.length;n<o;n++){this._unset(s,this.documentProperties[n])}this._unset(s,"cookie","",true)}this.loaded=true;setTimeout(function(){e.callback(e)},0)}},InitIframe:function(t){t=this.iframe||t;var e=t.contentWindow.document,i=this.GetHtml(this.config.stylesheets,this.editor.GetTemplateStyles());e.open("text/html","replace");e.write(i);e.close();this.GetWindow=function(){return t.contentWindow};this.GetDocument=function(){return t.contentWindow.document};this.editor.On("OnIframeInit")},GetHtml:function(t,e){var i="",s="",n;if(this.editor.config.bodyClass){i+=' class="'+this.editor.config.bodyClass+'"'}if(this.editor.config.bodyId){i+=' id="'+this.editor.config.bodyId+'"'}var o=this.editor.GetTemplateParams();if(o&&o["EDITOR_STYLES"]){for(n=0;n<o["EDITOR_STYLES"].length;n++){s+='<link data-bx-template-style="Y" rel="stylesheet" href="'+o["EDITOR_STYLES"][n]+"_"+this.editor.cssCounter++ +'">'}}t=typeof t==="string"?[t]:t;if(t){for(n=0;n<t.length;n++){s+='<link rel="stylesheet" href="'+t[n]+'">'}}s+='<link rel="stylesheet" href="'+this.editor.config.cssIframePath+"_"+this.editor.cssCounter++ +'">';if(typeof e==="string"){s+='<style type="text/css" data-bx-template-style="Y">'+e+"</style>"}if(this.editor.iframeCssText&&this.editor.iframeCssText.length>0){s+='<style type="text/css">'+this.editor.iframeCssText+"</style>"}return"<!DOCTYPE html><html><head>"+s+"</head><body"+i+"></body></html>"},_unset:function(t,e,i,s){try{t[e]=i}catch(n){}try{t.__defineGetter__(e,function(){return i})}catch(n){}if(s){try{t.__defineSetter__(e,function(){})}catch(n){}}if(!crashesWhenDefineProperty(e)){try{var o={get:function(){return i}};if(s){o.set=function(){}}Object.defineProperty(t,e,o)}catch(n){}}}};function n(e){this.editor=e;this.document=e.sandbox.GetDocument();BX.addCustomEvent(this.editor,"OnIframeReInit",BX.proxy(function(){this.document=this.editor.sandbox.GetDocument()},this));t.rangy.init()}n.prototype={GetBookmark:function(){if(!this.editor.synchro.IsFocusedOnTextarea()){var t=this.GetRange();return t&&t.cloneRange()}return false},SetBookmark:function(t){if(t&&this.editor.currentViewName!=="code"){this.SetSelection(t)}},SaveBookmark:function(){this.lastRange=this.GetBookmark();return this.lastRange},GetLastRange:function(){if(this.lastRange)return this.lastRange},RestoreBookmark:function(){if(this.lastRange){this.SetBookmark(this.lastRange);this.lastRange=false}},SetBefore:function(t){var e=rangy.createRange(this.document);e.setStartBefore(t);e.setEndBefore(t);return this.SetSelection(e)},SetAfter:function(t){var e=rangy.createRange(this.document);e.setStartAfter(t);e.setEndAfter(t);return this.SetSelection(e)},SelectNode:function(t){if(!t)return;var e=rangy.createRange(this.document),i=t.nodeType===1,s="canHaveHTML"in t?t.canHaveHTML:t.nodeName!=="IMG",n=i?t.innerHTML:t.data,o=n===""||n===this.editor.INVISIBLE_SPACE,r=BX.style(t,"display"),a=r==="block"||r==="list-item";if((BX.browser.IsIE()||BX.browser.IsIE10()||BX.browser.IsIE11())&&t&&BX.util.in_array(t.nodeName.toUpperCase(),this.editor.TABLE_TAGS)){if(t.tagName=="TABLE"||t.tagName=="TBODY"){var h=t.rows[0],l=t.rows[t.rows.length-1];e.setStartBefore(h.cells[0]);e.setEndAfter(l.cells[l.cells.length-1])}else if(t.tagName=="TR"||t.tagName=="TH"){e.setStartBefore(t.cells[0]);e.setEndAfter(t.cells[t.cells.length-1])}else{e.setStartBefore(t);e.setEndAfter(t)}this.SetSelection(e);return e}if(o&&i&&s){try{t.innerHTML=this.editor.INVISIBLE_SPACE}catch(d){}}if(s)e.selectNodeContents(t);else e.selectNode(t);if(s&&o&&i){e.collapse(a)}else if(s&&o){e.setStartAfter(t);e.setEndAfter(t)}try{this.SetSelection(e)}catch(d){}return e},GetSelectedNode:function(t){var e,i,s;if(t&&this.document.selection&&this.document.selection.type==="Control"){s=this.document.selection.createRange();if(s&&s.length){e=s.item(0)}}if(!e){i=this.GetSelection();if(i.focusNode===i.anchorNode){e=i.focusNode}}if(!e){s=this.GetRange();e=s?s.commonparentContainer:this.document.body}if(e&&e.ownerDocument!=this.editor.GetIframeDoc()){e=this.document.body}return e},ExecuteAndRestore:function(t,e){var i=this.document.body,s=e&&i.scrollTop,n=e&&i.scrollLeft,o="_bx-editor-temp-placeholder",r='<span class="'+o+'">'+this.editor.INVISIBLE_SPACE+"</span>",a=this.GetRange(),h;if(!a){t(i,i);return}var l=a.createContextualFragment(r);a.insertNode(l);try{t(a.startContainer,a.endContainer)}catch(d){setTimeout(function(){throw d},0)}if(document.querySelector){var c=this.document.querySelector("."+o);if(c){h=rangy.createRange(this.document);h.selectNode(c);h.deleteContents();this.SetSelection(h)}else{i.focus()}}if(e){i.scrollTop=s;i.scrollLeft=n}try{if(c.parentNode)c.parentNode.removeChild(c)}catch(u){}},ExecuteAndRestoreSimple:function(t){var e=this.GetRange(),i=this.document.body,s,n,o,r,a;if(!e){t(i,i);return}r=e.getNodes([3]);n=r[0]||e.startContainer;o=r[r.length-1]||e.endContainer;a={collapsed:e.collapsed,startContainer:n,startOffset:n===e.startContainer?e.startOffset:0,endContainer:o,endOffset:o===e.endContainer?e.endOffset:o.length};try{t(e.startContainer,e.endContainer)}catch(h){setTimeout(function(){throw h},0)}s=rangy.createRange(this.document);try{s.setStart(a.startContainer,a.startOffset)}catch(l){}try{s.setEnd(a.endContainer,a.endOffset)}catch(d){}try{this.SetSelection(s)}catch(c){}},InsertHTML:function(t,e){var i=rangy.createRangyRange(this.document),s=i.createContextualFragment(t),n=s.lastChild;this.InsertNode(s,e);if(n){this.SetAfter(n)}this.editor.On("OnInsertHtml")},InsertNode:function(t,e){if(!e||!e.isValid||!e.isValid())e=this.GetRange();if(e){e.insertNode(t)}this.editor.On("OnInsertHtml")},RemoveNode:function(t){this.editor.On("OnHtmlContentChangedByControl");var e=t.parentNode,i=t.nextSibling;BX.remove(t);this.editor.util.Refresh(e);if(i){this.editor.selection.SetBefore(i);this.editor.Focus()}this.editor.synchro.StartSync(100)},Surround:function(t,e){e=e||this.GetRange();if(e){try{e.surroundContents(t);this.SelectNode(t)}catch(i){t.appendChild(e.extractContents());e.insertNode(t)}}},ScrollIntoView:function(){var t,e=this,i=this.document,s=i.documentElement.scrollHeight>i.documentElement.offsetHeight;if(s){var n=i.__scrollIntoViewElement=i.__scrollIntoViewElement||function(){return BX.create("SPAN",{html:e.editor.INVISIBLE_SPACE},i)}(),o=0;this.InsertNode(n);if(n.parentNode){t=n;do{o+=t.offsetTop||0;t=t.offsetParent}while(t)}n.parentNode.removeChild(n);if(o>i.documentElement.scrollTop){i.documentElement.scrollTop=o}}},SelectLine:function(){var e="getSelection"in t&&"modify"in t.getSelection();if(e){var i=this.document.defaultView,s=i.getSelection();s.modify("move","left","lineboundary");s.modify("extend","right","lineboundary")}else if(this.document.selection){var n=this.document.selection.createRange(),o=n.boundingTop,r=n.boundingHeight,a=this.document.body.scrollWidth,h,l,d,c,u;if(!n.moveToPoint)return;if(o===0){d=this.document.createElement("span");this.insertNode(d);o=d.offsetTop;d.parentNode.removeChild(d)}o+=1;for(c=-10;c<a;c+=2){try{n.moveToPoint(c,o);break}catch(f){}}h=o;l=this.document.selection.createRange();for(u=a;u>=0;u--){try{l.moveToPoint(u,h);break}catch(m){}}n.setEndPoint("EndToEnd",l);n.select()}},GetText:function(){var t=this.GetSelection();return t?t.toString():""},GetNodes:function(t,e){var i=this.GetRange();if(i)return i.getNodes([t],e);else return[]},GetRange:function(t,e){if(!t){if(!this.editor.iframeView.IsFocused()&&e!==false){var i=this.editor.GetIframeDoc(),s=i.documentElement.scrollTop||i.body.scrollTop,n=i.documentElement.scrollLeft||i.body.scrollLeft;this.editor.iframeView.Focus();var o=i.documentElement.scrollTop||i.body.scrollTop,r=i.documentElement.scrollLeft||i.body.scrollLeft;if(o!==s||r!==n){var a=this.editor.sandbox.GetWindow();if(a)a.scrollTo(n,s)}}t=this.GetSelection()}return t&&t.rangeCount&&t.getRangeAt(0)},GetSelection:function(t){return rangy.getSelection(t||this.document.defaultView||this.document.parentWindow)},SetSelection:function(t){var e=this.document.defaultView||this.document.parentWindow,i=rangy.getSelection(e);return i.setSingleRange(t)},GetStructuralTags:function(){if(!this.structuralTags){var t=/^TABLE/i;this.structuralTags={LI:/^UL|OL|MENU/i,DT:/^DL/i,DD:/^DL/i,TD:t,TR:t,TH:t,TBODY:t,TFOOT:t,THEAD:t,CAPTION:t,COL:t,COLGROUP:t};this.structuralTagsMatchRe=/^LI|DT|DD|TD|TR|TH|TBODY|CAPTION|COL|COLGROUP|TFOOT|THEAD/i}return this.structuralTags},SetCursorBeforeNode:function(t){},_GetNonTextLastChild:function(t){var e=t.lastChild;while(e.nodeType!=1&&e.previousSibling)e=e.previousSibling;return e.nodeType==1?e:false},_GetNonTextFirstChild:function(t){var e=t.firstChild;while(e.nodeType!=1&&e.nextSibling)e=e.nextSibling;return e.nodeType==1?e:false},_MoveCursorBeforeNode:function(t){var e=this,i,s,n;this.GetStructuralTags();if(t.nodeType==1&&t.nodeName.match(this.structuralTagsMatchRe)){n=this._GetNonTextFirstChild(t.parentNode)===t;if(!n){return}i=this.structuralTags[t.nodeName];if(i){s=BX.findParent(t,function(t){if(t.nodeName.match(i)){return true}n=n&&e._GetNonTextFirstChild(t.parentNode)===t;return false},t.ownerDocument.BODY);if(s&&n){t=s}else{return}}}this.SetInvisibleTextBeforeNode(t)},_MoveCursorAfterNode:function(t){var e=this,i,s,n;this.GetStructuralTags();if(t.nodeType==1&&t.nodeName.match(this.structuralTagsMatchRe)){n=this._GetNonTextLastChild(t.parentNode)===t;if(!n){return}i=this.structuralTags[t.nodeName];if(i){s=BX.findParent(t,function(t){if(t.nodeName.match(i)){return true}n=n&&e._GetNonTextLastChild(t.parentNode)===t;return false},t.ownerDocument.BODY);if(s&&n){t=s}else{return}}}this.SetInvisibleTextAfterNode(t)},SaveRange:function(t){var e=this.GetRange(false,t);this.lastCheckedRange={endOffset:e.endOffset,endContainer:e.endContainer,range:e}},CheckLastRange:function(t){return this.lastCheckedRange&&this.lastCheckedRange.endOffset==t.endOffset&&this.lastCheckedRange.endContainer==t.endContainer},SetInvisibleTextAfterNode:function(t,e){var i=this.editor.util.GetInvisibleTextNode();if(t.nextSibling&&t.nextSibling.nodeType==3&&this.editor.util.IsEmptyNode(t.nextSibling)){this.editor.util.ReplaceNode(t.nextSibling,i)}else{this.editor.util.InsertAfter(i,t)}if(e){this.SetBefore(i)}else{this.SetAfter(i)}this.editor.Focus()},SetInvisibleTextBeforeNode:function(t){var e=this.editor.util.GetInvisibleTextNode();if(t.previousSibling&&t.previousSibling.nodeType==3&&this.editor.util.IsEmptyNode(t.previousSibling)){this.editor.util.ReplaceNode(t.previousSibling,e)}else{t.parentNode.insertBefore(e,t)}this.SetBefore(e);this.editor.Focus()},GetCommonAncestorForRange:function(t){return t.collapsed?t.startContainer:rangy.dom.getCommonAncestor(t.startContainer,t.endContainer)}};function o(t){this.isElementMerge=t.nodeType==1;this.firstTextNode=this.isElementMerge?t.lastChild:t;this.firstNode=t;this.textNodes=[this.firstTextNode]}o.prototype={DoMerge:function(){var t=true,e,i=this.textNodes.length,s=[],n,o,r;for(e=0;e<i;++e){n=this.textNodes[e];if(this.textNodes[e].nodeType!==3){return false}o=n.parentNode;s[e]=n.data;if(e){o.removeChild(n);if(!o.hasChildNodes())o.parentNode.removeChild(o)}}this.firstTextNode.data=r=s.join("");return r},GetLength:function(){var t=this.textNodes.length,e=0;while(t--)e+=this.textNodes[t].length;return e}};function r(t,e,i,s,n){this.editor=t;this.document=t.iframeView.document;this.tagNames=e||[defaultTagName];this.arStyle=i||{};this.cssClass=s||"";this.similarClassRegExp=null;this.normalize=n;this.applyToAnyTagName=false}r.prototype={GetStyledParent:function(t,e){e=e!==false;var i,s;while(t){if(t.nodeType==1){s=this.CheckCssStyle(t,e);i=this.CheckCssClass(t);if(BX.util.in_array(t.tagName.toLowerCase(),this.tagNames)&&i&&s)return t}t=t.parentNode}return false},CheckCssStyle:function(t,e){return this.editor.util.CheckCss(t,this.arStyle,e)},SimplifyNodesWithCss:function(t){var e,i=t.parentNode;if(i.childNodes.length==1){if(t.nodeName==i.nodeName){for(e in this.arStyle){if(this.arStyle.hasOwnProperty(e)&&t.style[e]){i.style[e]=t.style[e]}}this.editor.util.ReplaceWithOwnChildren(t)}else{for(e in this.arStyle){if(this.arStyle.hasOwnProperty(e)&&i.style[e]&&t.style[e]){i.style[e]=""}}}}},CheckCssClass:function(t){return!this.cssClass||this.cssClass&&BX.hasClass(t,this.cssClass)},PostApply:function(t,e){var i,s=t[0],n=t[t.length-1],r=[],a,h=s,l=n,d=0,c=n.length,u,f;for(i=0;i<t.length;++i){u=t[i];f=this.GetAdjacentMergeableTextNode(u.parentNode,false);if(f){if(!a){a=new o(f);r.push(a)}a.textNodes.push(u);if(u===s){h=a.firstTextNode;d=h.length}if(u===n){l=a.firstTextNode;c=a.GetLength()}}else{a=null}}var m=this.GetAdjacentMergeableTextNode(n.parentNode,true);if(m){if(!a){a=new o(n);r.push(a)}a.textNodes.push(m)}if(r.length){for(i=0;i<r.length;++i)r[i].DoMerge();e.setStart(h,d);e.setEnd(l,c)}t=e.getNodes([3]);for(i=0;i<t.length;++i){u=t[i];this.SimplifyNodesWithCss(u.parentNode)}},NormalizeNewNode:function(t,e){var i=t.parentNode;if(i&&i.nodeName!=="BODY"){var s=this.GetNonEmptyChilds(i),n=this.CheckCssStyle(i,false),o=this.CheckCssClass(i);if(s.length==1&&i.nodeName==t.nodeName&&n&&o){i.parentNode.insertBefore(t,i);BX.remove(i)}}return e},GetNonEmptyChilds:function(t){var e,i=t.childNodes,s=[];for(e=0;e<i.length;e++){if(i[e].nodeType==1||i[e].nodeType==3&&i[e].nodeValue!=""&&i[e].nodeValue!=this.editor.INVISIBLE_SPACE&&!i[e].nodeValue.match(/^[\s\n\r\t]+$/gi)){s.push(i[e])}}return s},GetAdjacentMergeableTextNode:function(t,e){var i=t.nodeType==3,s=i?t.parentNode:t,n,o=e?"nextSibling":"previousSibling";if(i){n=t[o];if(n&&n.nodeType==3){return n}}else{n=s[o];if(n&&this.AreElementsMergeable(t,n)){return n[e?"firstChild":"lastChild"]}}return null},AreElementsMergeable:function(t,e){return rangy.dom.arrayContains(this.tagNames,(t.tagName||"").toLowerCase())&&rangy.dom.arrayContains(this.tagNames,(e.tagName||"").toLowerCase())&&t.className.replace(/\s+/g," ")==e.className.replace(/\s+/g," ")&&this.CompareNodeAttributes(t,e)},CompareNodeAttributes:function(t,e){if(t.attributes.length!=e.attributes.length)return false;var i,s=t.attributes.length,n,o,r;for(i=0;i<s;i++){n=t.attributes[i];r=n.name;if(r!="class"){o=e.attributes.getNamedItem(r);if(n.specified!=o.specified)return false;if(n.specified&&n.nodeValue!==o.nodeValue){return false}}}return true},CreateContainer:function(){var t=this.document.createElement(this.tagNames[0]);if(this.cssClass){t.className=this.cssClass}if(this.arStyle){for(var e in this.arStyle){if(this.arStyle.hasOwnProperty(e)){t.style[e]=this.arStyle[e]}}}return t},ApplyToTextNode:function(t){var e=t.parentNode,i;if(e.childNodes.length==1&&BX.util.in_array(e.tagName.toLowerCase(),this.tagNames)){if(this.cssClass){BX.addClass(e,this.cssClass)}if(this.arStyle){for(i in this.arStyle){if(this.arStyle.hasOwnProperty(i)){e.style[i]=this.arStyle[i]}}}}else{if(e.childNodes.length==1){if(this.cssClass&&BX.hasClass(e,this.cssClass)){BX.removeClass(e,this.cssClass)}if(this.arStyle){for(i in this.arStyle){if(this.arStyle.hasOwnProperty(i)&&e.style[i]){e.style[i]=""}}}}var s=this.CreateContainer();t.parentNode.insertBefore(s,t);s.appendChild(t)}},IsRemovable:function(t){return rangy.dom.arrayContains(this.tagNames,t.tagName.toLowerCase())&&BX.util.trim(t.className)==this.cssClass},IsAvailableTextNodeParent:function(t){return t&&t.nodeName&&t.nodeName!=="OL"&&t.nodeName!=="UL"&&t.nodeName!=="MENU"&&t.nodeName!=="TBODY"&&t.nodeName!=="TFOOT"&&t.nodeName!=="THEAD"&&t.nodeName!=="TABLE"&&t.nodeName!=="DL"},UndoToTextNode:function(t,e,i){if(!e.containsNode(i)){var s=e.cloneRange();s.selectNode(i);if(s.isPointInRange(e.endContainer,e.endOffset)&&this.editor.util.IsSplitPoint(e.endContainer,e.endOffset)&&e.endContainer.nodeName!=="BODY"){this.editor.util.SplitNodeAt(i,e.endContainer,e.endOffset);e.setEndAfter(i)}if(s.isPointInRange(e.startContainer,e.startOffset)&&this.editor.util.IsSplitPoint(e.startContainer,e.startOffset)&&e.startContainer.nodeName!=="BODY"){i=this.editor.util.SplitNodeAt(i,e.startContainer,e.startOffset)}}if(i&&i.nodeName!="BODY"&&this.IsRemovable(i)){if(this.arStyle){for(var n in this.arStyle){if(this.arStyle.hasOwnProperty(n)&&i.style[n]){i.style[n]=""}}}if(!i.style.cssText||BX.util.trim(i.style.cssText)===""){this.editor.util.ReplaceWithOwnChildren(i)}else if(this.tagNames.length>1||this.tagNames[0]!=="span"){this.editor.util.RenameNode(i,"span")}}},ApplyToRange:function(t){var e=t.getNodes([3]);if(!e.length){try{var i=this.CreateContainer();t.surroundContents(i);t=this.NormalizeNewNode(i,t);this.SelectNode(t,i);return t}catch(s){}}t.splitBoundaries();e=t.getNodes([3]);if(!e.length&&t.collapsed&&t.startContainer==t.endContainer){var n=this.editor.util.GetInvisibleTextNode();this.editor.selection.InsertNode(n);e=[n]}if(e.length){var o;for(var r=0,a=e.length;r<a;++r){o=e[r];if(!this.GetStyledParent(o)&&this.IsAvailableTextNodeParent(o.parentNode)){this.ApplyToTextNode(o)}}t.setStart(e[0],0);o=e[e.length-1];t.setEnd(o,o.length);if(this.normalize){this.PostApply(e,t)}}return t},UndoToRange:function(t,e){var i=t.getNodes([3]),s,n;e=e!==false;if(i.length){t.splitBoundaries();i=t.getNodes([3])}else{var o=this.editor.util.GetInvisibleTextNode();t.insertNode(o);t.selectNode(o);i=[o]}var r,a,h=[];for(r=0,a=i.length;r<a;r++){h.push({node:i[r],nesting:this.GetNodeNesting(i[r])})}h=h.sort(function(t,e){return e.nesting-t.nesting});for(r=0,a=h.length;r<a;r++){s=h[r].node;n=this.GetStyledParent(s,e);if(n){this.UndoToTextNode(s,t,n);t=this.editor.selection.GetRange()}}if(a==1){this.SelectNode(t,i[0])}else{t.setStart(i[0],0);t.setEnd(i[i.length-1],i[i.length-1].length);this.editor.selection.SetSelection(t);if(this.normalize){this.PostApply(i,t)}}return t},GetNodeNesting:function(t){return this.editor.util.GetNodeDomOffset(t)},SelectNode:function(t,e){var i=e.nodeType===1,s="canHaveHTML"in e?e.canHaveHTML:true,n=i?e.innerHTML:e.data,o=n===""||n===this.editor.INVISIBLE_SPACE;if(o&&i&&s){try{e.innerHTML=this.editor.INVISIBLE_SPACE}catch(r){}}t.selectNodeContents(e);if(o&&i){t.collapse(false)}else if(o){t.setStartAfter(e);t.setEndAfter(e)}},GetTextSelectedByRange:function(t,e){var i=e.cloneRange();i.selectNodeContents(t);var s=i.intersection(e);var n=s?s.toString():"";i.detach();return n},IsAppliedToRange:function(t,e){var i=[],s,n=t.getNodes([3]);e=e!==false;if(!n.length){s=this.GetStyledParent(t.startContainer,e);return s?[s]:false}var o,r;for(o=0;o<n.length;++o){r=this.GetTextSelectedByRange(n[o],t);s=this.GetStyledParent(n[o],e);if(r!=""&&!s){return false}else{i.push(s)}}return i},ToggleRange:function(t){return this.IsAppliedToRange(t)?this.UndoToRange(t):this.ApplyToRange(t)}};function a(t){this.editor=t;this.history=[this.editor.iframeView.GetValue()];this.position=1;this.document=t.sandbox.GetDocument();this.historyLength=30;BX.addCustomEvent(this.editor,"OnIframeReInit",BX.proxy(function(){this.document=this.editor.sandbox.GetDocument()},this));this.Init()}a.prototype={Init:function(){var t=this;BX.addCustomEvent(this.editor,"OnHtmlContentChangedByControl",BX.delegate(this.Transact,this));BX.addCustomEvent(this.editor,"OnIframeNewWord",BX.delegate(this.Transact,this));BX.addCustomEvent(this.editor,"OnIframeKeyup",BX.delegate(this.Transact,this));BX.addCustomEvent(this.editor,"OnBeforeCommandExec",function(e){if(e){t.Transact()}});BX.addCustomEvent(this.editor,"OnIframeKeydown",BX.proxy(this.Keydown,this))},Keydown:function(t,e,i,s){if((t.ctrlKey||t.metaKey)&&!t.altKey){var n=e===this.editor.KEY_CODES["z"]&&!t.shiftKey,o=e===this.editor.KEY_CODES["z"]&&t.shiftKey||e===this.editor.KEY_CODES["y"];if(n){this.Undo();return BX.PreventDefault(t)}else if(o){this.Redo();return BX.PreventDefault(t)}}if(e!==this.lastKey){if(e===this.editor.KEY_CODES["backspace"]||e===this.editor.KEY_CODES["delete"]){this.Transact()}this.lastKey=e}},Transact:function(){var t=this.history[this.position-1],e=this.editor.iframeView.GetValue();if(e!==t){var i=this.history.length=this.position;if(i>this.historyLength){this.history.shift();this.position--}this.position++;this.history.push(e);this.CheckControls()}},Undo:function(){if(this.position>1){this.Transact();this.position--;this.Set(this.history[this.position-1]);this.editor.On("OnUndo");this.CheckControls()}},Redo:function(){if(this.position<this.history.length){this.position++;this.Set(this.history[this.position-1]);this.editor.On("OnRedo");this.CheckControls()}},Set:function(t){this.editor.iframeView.SetValue(t);this.editor.Focus(true)},CheckControls:function(){this.editor.On("OnEnableUndo",[this.position>1]);this.editor.On("OnEnableRedo",[this.position<this.history.length])}};function h(t){this.editor=t;this.arStyles={};this.sStyles=""}h.prototype={GetIframe:function(t){if(!this.cssIframe){this.cssIframe=this.CreateIframe(t)}return this.cssIframe},CreateIframe:function(t){this.cssIframe=document.body.appendChild(BX.create("IFRAME",{
props:{className:"bx-editor-css-iframe"}}));this.iframeDocument=this.cssIframe.contentDocument||this.cssIframe.contentWindow.document;this.iframeDocument.open("text/html","replace");this.iframeDocument.write('<!DOCTYPE html><html><head><style type="text/css" data-bx-template-style="Y">'+t+"</style></head><body></body></html>");this.iframeDocument.close();return this.cssIframe},GetCSS:function(t,e,i,s){if(!this.arStyles[t]){if(!this.cssIframe){this.cssIframe=this.CreateIframe(e)}else{var n,o=this.iframeDocument,r=o.head||o.getElementsByTagName("HEAD")[0],a=r.getElementsByTagName("STYLE");for(n=0;n<a.length;n++){if(a[n].getAttribute("data-bx-template-style")=="Y")BX.cleanNode(a[n],true)}if(e){r.appendChild(BX.create("STYLE",{props:{type:"text/css"},text:e},o)).setAttribute("data-bx-template-style","Y")}}this.arStyles[t]=this.ParseCss()}var h=this.arStyles[t];if(s){var l=[],d;if(typeof s!="object"){s=[s]}s.push("DEFAULT");for(n=0;n<s.length;n++){d=s[n];if(h[d]&&typeof h[d]=="object"){l=l.concat(h[d])}}h=l}return h},ParseCss:function(){var t=this.iframeDocument,e=[],i={},s,n,o,r,a,h,l,d,c,u,f;if(!t.styleSheets){return i}var m=t.styleSheets,p=this.editor.GetStylesDescription();for(r=0,c=m.length;r<c;r++){s=m[r].rules?m[r].rules:m[r].cssRules;for(a=0,u=s.length;a<u;a++){if(s[a].type!=s[a].STYLE_RULE){continue}n=s[a].selectorText;o=n.split(",");for(h=0,f=o.length;h<f;h++){l=o[h].split(" ");l=l[l.length-1].trim();if(l.substr(0,1)=="."){l=l.substr(1);d="DEFAULT"}else{d=l.split(".");l=d.length>1?d[1]:"";d=d[0].toUpperCase()}if(!e[l]){e[l]=true;if(!i[d]){i[d]=[]}i[d].push({className:l,classTitle:p[l]||null,original:o[h],cssText:s[a].style.cssText})}}}}return i}};e={classes:{},tags:{b:{clean_empty:true},strong:{clean_empty:true},i:{clean_empty:true},em:{clean_empty:true},u:{clean_empty:true},del:{clean_empty:true},s:{rename_tag:"del"},strike:{rename_tag:"del"},h1:{},h2:{},h3:{},h4:{},h5:{},h6:{},span:{clean_empty:true},p:{},br:{},div:{},hr:{},nobr:{},code:{},section:{},figure:{},figcaption:{},fieldset:{},menu:{rename_tag:"ul"},ol:{},ul:{},li:{},pre:{},table:{},tr:{},td:{check_attributes:{rowspan:"numbers",colspan:"numbers"}},tbody:{},tfoot:{},thead:{},th:{check_attributes:{rowspan:"numbers",colspan:"numbers"}},caption:{},col:{},colgroup:{},dl:{rename_tag:""},dd:{rename_tag:""},dt:{rename_tag:""},iframe:{},noindex:{},font:{replace_with_children:1},embed:{},noembed:{},object:{},param:{},sup:{},sub:{},address:{},nav:{},aside:{},article:{},main:{},acronym:{},abbr:{},label:{},time:{},small:{},big:{},title:{remove:1},area:{remove:1},command:{remove:1},noframes:{remove:1},bgsound:{remove:1},basefont:{remove:1},head:{remove:1},track:{remove:1},wbr:{remove:1},noscript:{remove:1},svg:{remove:1},keygen:{remove:1},meta:{remove:1},isindex:{remove:1},base:{remove:1},video:{remove:1},canvas:{remove:1},applet:{remove:1},spacer:{remove:1},source:{remove:1},frame:{remove:1},style:{remove:1},device:{remove:1},xml:{remove:1},nextid:{remove:1},audio:{remove:1},link:{remove:1},script:{remove:1},comment:{remove:1},frameset:{remove:1},details:{rename_tag:"div"},multicol:{rename_tag:"div"},footer:{rename_tag:"div"},map:{rename_tag:"div"},body:{rename_tag:"div"},html:{rename_tag:"div"},hgroup:{rename_tag:"div"},listing:{rename_tag:"div"},header:{rename_tag:"div"},rt:{rename_tag:"span"},xmp:{rename_tag:"span"},bdi:{rename_tag:"span"},progress:{rename_tag:"span"},dfn:{rename_tag:"span"},rb:{rename_tag:"span"},mark:{rename_tag:"span"},output:{rename_tag:"span"},marquee:{rename_tag:"span"},rp:{rename_tag:"span"},summary:{rename_tag:"span"},"var":{rename_tag:"span"},tt:{rename_tag:"span"},blink:{rename_tag:"span"},plaintext:{rename_tag:"span"},legend:{rename_tag:"span"},kbd:{rename_tag:"span"},meter:{rename_tag:"span"},datalist:{rename_tag:"span"},samp:{rename_tag:"span"},bdo:{rename_tag:"span"},ruby:{rename_tag:"span"},ins:{rename_tag:"span"},optgroup:{rename_tag:"span"},form:{},option:{},select:{},textarea:{},button:{},input:{},dir:{rename_tag:"ul"},a:{},img:{check_attributes:{width:"numbers",alt:"alt",src:"url",height:"numbers"},add_class:{align:"align_img"}},q:{check_attributes:{cite:"url"}},blockquote:{check_attributes:{cite:"url"}},center:{rename_tag:"div",add_css:{textAlign:"center"}},cite:{}}}})(window);
/* End */
;
//# sourceMappingURL=kernel_htmleditor.map.js
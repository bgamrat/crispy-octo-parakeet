define([
    "dojo/dom",
    "dojo/dom-attr",
    "dojo/dom-construct",
    "dojo/on",
    "dojo/query",
    "dijit/form/ValidationTextBox",
    "dijit/form/CheckBox",
    "dijit/form/Button",
    "app/lib/common",
    "dojo/i18n!app/nls/core",
    "dojo/NodeList-dom",
    "dojo/NodeList-traverse",
    "dojo/domReady!"
], function (dom, domAttr, domConstruct,
        on, query,
        ValidationTextBox, CheckBox, Button,
        lib, core) {
    //"use strict";

    var dataPrototype, prototypeNode, prototypeContent;
    var statusInput = [], commentInput = [], inUseCheckBox = [];
    var addOneMoreControl = null;
    var divId = "employment_statuses_statuses";

    function cloneNewNode() {
        prototypeContent = dataPrototype.replace(/__status__/g, statusInput.length);
        domConstruct.place(prototypeContent, prototypeNode.parentNode, "last");
    }

    function createDijits(newRow) {
        var dijit, index = statusInput.length;
        var base = divId + '_' + index + '_';
        dijit = new ValidationTextBox({
            placeholder: core.name,
            trim: true,
            pattern: "[a-zA-Z0-9x\.\,\ \+\(\)-]{2,24}",
            required: true,
            name: "employment_statuses[statuses][" + index + "][name]",
            value: document.getElementById(base + "name").value
        }, base + "name");
        statusInput.push(dijit);
        dijit.startup();
        dijit = new ValidationTextBox({
            placeholder: core.comment,
            trim: true,
            required: false,
            name: "employment_statuses[statuses][" + index + "][comment]",
            value: document.getElementById(base + "comment").value
        }, base + "comment");
        commentInput.push(dijit);
        dijit.startup();
        dijit = new CheckBox({'checked': document.getElementById(base + "in_use").checked || newRow === true,
            name: "employment_statuses[statuses][" + index + "][in_use]"}, base + "in_use");
        inUseCheckBox.push(dijit);
        dijit.startup();
    }

    function run() {
        var i, existingStatusRows;

        prototypeNode = dom.byId(divId);

        if( prototypeNode === null ) {
            lib.textError(divId + " not found");
            return;
        }

        existingStatusRows = query('.form-row.status');
        existingStatusRows = existingStatusRows.length;

        for( i = 0; i < existingStatusRows; i++ ) {
            createDijits(false);
        }

        dataPrototype = domAttr.get(prototypeNode, "data-prototype");
        prototypeContent = dataPrototype.replace(/__status__/g, statusInput.length);

        addOneMoreControl = query('.add-one-more-row');
        addOneMoreControl.on("click", function (event) {
            cloneNewNode();
            createDijits(true);
        });

        var saveBtn = new Button({
            label: core.save,
            type: "submit"
        }, 'save-btn');
        saveBtn.startup();

        lib.pageReady();
    }

    return {
        run: run
    }
});
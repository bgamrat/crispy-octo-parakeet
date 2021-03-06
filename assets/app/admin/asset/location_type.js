define([
    "dojo/dom",
    "dojo/dom-attr",
    "dojo/dom-construct",
    "dojo/on",
    "dojo/query",
    "dojo/data/ObjectStore",
    "dojo/store/Memory",
    "dijit/form/Form",
    "dijit/form/Select",
    "dijit/form/ValidationTextBox",
    "dijit/form/CheckBox",
    "dijit/form/RadioButton",
    "dijit/form/Button",
    "app/lib/common",
    "dojo/i18n!app/nls/core",
    "dojo/NodeList-dom",
    "dojo/NodeList-traverse",
    "dojo/domReady!"
], function (dom, domAttr, domConstruct, on,
        query, ObjectStore, Memory,
        Form, Select, ValidationTextBox, CheckBox, RadioButton, Button,
        lib, core) {
    //"use strict";

    var dataPrototype, prototypeNode, prototypeContent;
    var nameInput = [], entitySelect = [], urlInput = [], inUseCheckBox = [], defaultRadioButton = [];
    var addOneMoreControl = null;
    var divId = "location_types_types";
    var store;

    function cloneNewNode() {
        prototypeContent = dataPrototype.replace(/__type__/g, nameInput.length);
        domConstruct.place(prototypeContent, prototypeNode.parentNode, "last");
    }

    function createDijits(newRow) {
        var dijit, index = nameInput.length, el;
        var base = divId + '_' + index + '_';
        var checked = false;
        el = document.getElementById(base + "name");
        dijit = new ValidationTextBox({
            placeholder: core.name,
            trim: true,
            pattern: "[a-zA-Z0-9x\.\,\ \+\(\)-]{2,24}",
            required: true,
            name: "location_types[types][" + index + "][name]",
            value: el.value,
            readonly: el.readonly
        }, base + "name");
        nameInput.push(dijit);
        dijit.startup();
        dijit = new Select({
            store: store,
            placeholder: core.entity,
            name: "location_types[types][" + index + "][entity]",
            required: true,
            value: domAttr.get(dom.byId(base + "entity"), 'data-selected')
        }, base + "entity");
        dijit.startup();
        entitySelect.push(dijit);
        dijit = new ValidationTextBox({
            placeholder: core.url,
            trim: true,
            required: false,
            name: "location_types[types][" + index + "][url]",
            value: document.getElementById(base + "url").value
        }, base + "url");
        dijit.startup();
        urlInput.push(dijit);
        dijit = new CheckBox({'checked': document.getElementById(base + "in_use").value === "1" || document.getElementById(base + "in_use").checked || newRow === true,
            name: "location_types[types][" + index + "][in_use]"}, base + "in_use");
        inUseCheckBox.push(dijit);
        dijit.startup();
        checked = dom.byId(base + "default").checked === true;
        dijit.startup();
        dijit = new RadioButton({
            name: "location_types[types][" + index + "][default]"
        }, base + "default");
        dijit.set("checked", checked);
        dijit.startup();
        defaultRadioButton.push(dijit);
    }

    function run() {
        var base, i, existingLocationRows;

        prototypeNode = dom.byId(divId);

        if( prototypeNode === null ) {
            lib.textError(divId + " not found");
            return;
        }

        var select = "location_types_types_0_entity";
        var data = JSON.parse(domAttr.get(select, "data-options"));
        // Convert the data to an array of objects
        var entityStoreData = [], d;
        for( d in data ) {
            entityStoreData.push(data[d]);
        }
        var entityMemoryStore = new Memory({
            idProperty: "value",
            data: entityStoreData});
        store = new ObjectStore({objectStore: entityMemoryStore});

        existingLocationRows = query('.location-types .form-row.type');
        existingLocationRows = existingLocationRows.length;

        for( i = 0; i < existingLocationRows; i++ ) {
            createDijits(false);
        }

        dataPrototype = domAttr.get(prototypeNode, "data-prototype");
        prototypeContent = dataPrototype.replace(/__type__/g, nameInput.length);

        addOneMoreControl = query('.location-types .add-one-more-row');

        addOneMoreControl.on("click", function (event) {
            cloneNewNode();
            createDijits(true);
        });

        var locationTypesForm = new Form({}, '[name="location_types"]');
        locationTypesForm.startup();

        var saveBtn = new Button({
            label: core.save,
            type: "submit"
        }, 'location-types-save-btn');
        saveBtn.startup();

        on(dom.byId("location_types_types"), "click", function (event) {
            var target = event.target;
            var id = target.id;
            if( id.indexOf("default") !== -1 ) {
                if( target.checked ) {
                    id = id.replace(/^.*(\d+).*$/, '$1');
                    target.name = 'location_types[types][' + id + '][default]';
                } else {
                    target.removeAttribute("name");
                }
            }
        });

        lib.pageReady();
    }

    return {
        run: run
    }
});
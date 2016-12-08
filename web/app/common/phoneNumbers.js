define([
    "dojo/dom",
    "dojo/dom-attr",
    "dojo/dom-construct",
    "dojo/on",
    "dojo/query",
    "dojo/data/ObjectStore",
    "dojo/store/Memory",
    "dijit/form/ValidationTextBox",
    "dijit/form/Select",
    "app/lib/common",
    "dojo/i18n!app/nls/core",
    "dojo/NodeList-dom",
    "dojo/NodeList-traverse",
    "dojo/domReady!"
], function (dom, domAttr, domConstruct, on,
        query, ObjectStore, Memory,
        ValidationTextBox, Select,
        lib, core) {
    "use strict";

    var dataPrototype, prototypeNode, prototypeContent;
    var store;
    var phoneNumberId = [], typeSelect = [], numberInput = [], commentInput = [];
    var divIdInUse = null;
    var addOneMoreControl = null;

    function getDivId() {
        return divIdInUse;
    }

    function setDivId(divId) {
        divIdInUse = divId + '_phones';
    }

    function cloneNewNode() {
        prototypeContent = dataPrototype.replace(/__phone__/g, numberInput.length);
        domConstruct.place(prototypeContent, prototypeNode.parentNode, "last");
    }

    function createDijits() {
        var dijit;
        var base = getDivId() + '_' + numberInput.length + '_';
        phoneNumberId.push(null);
        dijit = new Select({
            store: store,
            placeholder: core.type,
            required: true
        }, base + "type");
        typeSelect.push(dijit);
        dijit.startup();
        dijit = new ValidationTextBox({
            placeholder: core.phone_number,
            trim: true,
            pattern: "[0-9x\.\,\ \+\(\)-]{2,24}",
            required: true
        }, base + "phoneNumber");
        numberInput.push(dijit);
        dijit.startup();
        dijit = new ValidationTextBox({
            placeholder: core.comment,
            trim: true,
            required: false
        }, base + "comment");
        commentInput.push(dijit);
        dijit.startup();
    }

    function destroyRow(id, target) {
        for( i = 0; i < phoneNumberId.length; i++ ) {
            if( phoneNumberId[i] === id ) {
                id = i;
                break;
            }
        }
        phoneNumberId.splice(id, 1);
        typeSelect.pop().destroyRecursive();
        numberInput.pop().destroyRecursive();
        commentInput.pop().destroyRecursive();
        domConstruct.destroy(target);
    }

    function run() {

        var base, select, data, storeData, d, memoryStore;
        if( arguments.length > 0 ) {
            setDivId(arguments[0]);
        }

        prototypeNode = dom.byId(getDivId());
        if( prototypeNode === null ) {
            setDivId(arguments[0] + '_0');
            prototypeNode = dom.byId(getDivId());
        }

        if( prototypeNode === null ) {
            lib.textError(getDivId() + " not found");
            return;
        }

        dataPrototype = domAttr.get(prototypeNode, "data-prototype");
        prototypeContent = dataPrototype.replace(/__phone__/g, numberInput.length);
        domConstruct.place(prototypeContent, prototypeNode.parentNode, "last");

        base = prototypeNode.id + "_" + numberInput.length;
        select = base + "_type";

        if( dom.byId(select) === null ) {
            lib.textError(base + " not found");
            return;
        }

        data = JSON.parse(domAttr.get(select, "data-options"));
        // Convert the data to an array of objects
        storeData = [];
        storeData.push({value: "", label: core.type.toLowerCase()});
        for( d in data ) {
            storeData.push(data[d]);
        }
        memoryStore = new Memory({
            idProperty: "value",
            data: storeData});
        store = new ObjectStore({objectStore: memoryStore});

        createDijits();

        addOneMoreControl = query('.phone-numbers .add-one-more-row');

        addOneMoreControl.on("click", function (event) {
            cloneNewNode();
            createDijits();
            if( numberInput.length >= lib.constant.MAX_PHONE_NUMBERS ) {
                addOneMoreControl.addClass("hidden");
            }
        });

        on(prototypeNode.parentNode, ".remove-form-row:click", function (event) {
            var target = event.target;
            var targetParent = target.parentNode;
            var id = parseInt(targetParent.id.replace(/\D/g, ''));
            destroyRow(id, targetParent.parentNode);
            if( numberInput.length <= lib.constant.MAX_PHONE_NUMBERS ) {
                addOneMoreControl.removeClass("hidden");
            }
        });
    }

    function getData() {
        var i, returnData = [], phone;
        for( i = 0; i < numberInput.length; i++ ) {
            phone = numberInput[i].get('value').trim();
            if (phone !== "") {
                returnData.push(
                    {
                        "id": phoneNumberId[i],
                        "type": parseInt(typeSelect[i].get('value')),
                        "phoneNumber": phone,
                        "comment": commentInput[i].get('value')
                    });
            }
        }
        return returnData.length > 0 ? returnData : null;
    }

    function setData(phones) {
        var i, obj;

        query(".form-row.phone-number").forEach(function (node, index) {
            if( index !== 0 ) {
                destroyRow(index, node);
            }
        });

        if( typeof phones === "object" && phones !== null && phones.length > 0 ) {

            for( i = 0; i < numberInput.length; i++ ) {
                if( i !== 0 ) {
                    cloneNewNode();
                    createDijits();
                }
                obj = phones[i];
                phoneNumberId[i] = obj.id;
                typeSelect[i].set('value', obj.type.id);
                numberInput[i].set('value', obj.phoneNumber);
                commentInput[i].set('value', obj.comment);
            }
        } else {
            phoneNumberId[0] = null;
            typeSelect[0].set('value', "");
            numberInput[0].set('value', "");
            commentInput[0].set('value', "");
        }
    }

    return {
        run: run,
        getData: getData,
        setData: setData
    }
});
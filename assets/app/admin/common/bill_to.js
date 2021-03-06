define([
    "dojo/dom",
    "dojo/dom-attr",
    "dojo/dom-construct",
    "dojo/on",
    "dojo/query",
    "dojo/aspect",
    'dojo/store/JsonRest',
    "dijit/form/CurrencyTextBox",
    "dijit/form/ValidationTextBox",
    "dijit/form/FilteringSelect",
    "app/lib/common",
    "dojo/i18n!app/nls/core",
    "dojo/i18n!app/nls/schedule",
    "dojo/NodeList-dom",
    "dojo/NodeList-traverse",
    "dojo/domReady!"
], function (dom, domAttr, domConstruct, on, query, aspect,
        JsonRest,
        CurrencyTextBox, ValidationTextBox, FilteringSelect,
        lib, core, schedule) {
    //"use strict";

    var dataPrototype;
    var prototypeNode, prototypeContent;
    var contactFilteringSelect = [], eventFilteringSelect = [], amountInput = [], commentInput = [];
    var contactStore, eventStore;
    var divIdInUse = null;
    var addOneMoreControl = null;

    function getDivId() {
        return divIdInUse;
    }

    function setDivId(divId) {
        divIdInUse = divId + '_bill_tos';
    }

    function cloneNewNode() {
        prototypeContent = dataPrototype.replace(/__bill_to__/g, contactFilteringSelect.length);
        domConstruct.place(prototypeContent, prototypeNode, "after");
    }

    function createDijits() {
        var dijit;
        var base = prototypeNode.id + "_" + contactFilteringSelect.length + "_";
        dijit = new FilteringSelect({
            store: contactStore,
            labelAttr: "label",
            labelType: "html",
            searchAttr: "name",
            placeholder: core.contact,
            required: false,
            pageSize: 25
            //intermediateChanges: true
        }, base + "contact");
        dijit.startup();
        dijit.on("change", function (evt) {
            var id = parseInt(this.id.replace(/\D/g, ''));
            var item = this.get('item');
            if (item !== null && typeof item.contact !== "undefined") {
                eventFilteringSelect[id].store.target = "/api/store/events?" + lib.contactTypes[item.type.id] + "=" + item.contact.entity.id;
            }
        });
        contactFilteringSelect.push(dijit);
        dijit = new FilteringSelect({
            store: eventStore,
            labelAttr: "name",
            searchAttr: "name",
            placeholder: schedule.event,
            required: false,
            pageSize: 25
        }, base + "event");
        eventFilteringSelect.push(dijit);
        dijit.startup();
        dijit = new CurrencyTextBox({
            placeholder: core.amount,
            trim: true,
            required: false
        }, base + "amount");
        amountInput.push(dijit);
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
        var i, l, kid, item;

        l = contactFilteringSelect.length;
        for( i = 0; i < l; i++ ) {
            kid = contactFilteringSelect[i].id.replace(/\D/g, '');
            if( kid == id ) {
                id = i;
                break;
            }
        }

        item = contactFilteringSelect.splice(id, 1);
        item[0].destroyRecursive();
        item = eventFilteringSelect.splice(id, 1);
        item[0].destroyRecursive();
        item = amountInput.splice(id, 1);
        item[0].destroyRecursive();
        item = commentInput.splice(id, 1);
        item[0].destroyRecursive();
        domConstruct.destroy(target);
    }

    function run() {

        if( arguments.length > 0 ) {
            setDivId(arguments[0]);
        } else {
            throw new Error('No divId');
        }

        lib.getContactTypes();

        prototypeNode = dom.byId(getDivId());
        dataPrototype = domAttr.get(prototypeNode, "data-prototype");
        prototypeContent = dataPrototype.replace(/__bill_to__/g, '0');

        domConstruct.place(prototypeContent, prototypeNode, "after");

        contactStore = new JsonRest({
            target: '/api/store/contacts?client&venue',
            useRangeHeaders: false,
            idProperty: 'hash'});

        eventStore = new JsonRest({
            target: '/api/store/events?contact=',
            useRangeHeaders: false,
            idProperty: 'id'});

        createDijits();

        addOneMoreControl = query('.bill-tos .add-one-more-row');

        addOneMoreControl.on("click", function (event) {
            cloneNewNode();
            createDijits();
        });

        on(prototypeNode.parentNode, ".remove-form-row:click", function (event) {
            var target = event.target;
            var targetParent = target.parentNode;
            var id = parseInt(targetParent.id.replace(/\D/g, ''));
            destroyRow(id, target.closest(".form-row.bill-to"));
        });
    }

    function getData() {
        var i, l = contactFilteringSelect.length, contact, contactData, returnData = [];
        for( i = 0; i < l; i++ ) {
            contact = contactFilteringSelect[i].get('item');
            if( contact !== null ) {
                contactData = {
                    "id": contact.id,
                    "contact_entity_id": contact.entity,
                    "contact_type": contact.type.entity,
                    "person_id": contact.person.id,
                    "name": contact.name
                };
                if( typeof contact.address_id !== "undefined" ) {
                    contactData.address_id = contact.address_id;
                } else {
                    contactData.address_id = null;
                }
                ;

                returnData.push(
                        {
                            "contact": contactData,
                            "event": eventFilteringSelect[i].get('value'),
                            "amount": parseFloat(amountInput[i].get("value")),
                            "comment": commentInput[i].get('value')
                        });
            }
        }
        return returnData;
    }

    function setData(items) {
        var i, l, obj, nodes;

        nodes = query(".form-row.bill-to", "bill-tos");
        nodes.forEach(function (node, index) {
            destroyRow(0, node);
        });

        if( items !== null && typeof items === "object" && items.length !== 0 ) {
            l = items.length;
            for( i = 0; i < l; i++ ) {
                cloneNewNode();
                createDijits();
                obj = items[i];
                contactFilteringSelect[i].set('item', obj.contact);
                amountInput[i].set("value", obj.amount);
                commentInput[i].set('value', obj.comment);
                if( typeof items[i].event !== "undefined" && items[i].event !== null && typeof items[i].event.name !== "undefined" ) {
                    eventStore.target = eventStore.target.replace(/\d*$/, obj.contact.id);
                    eventFilteringSelect[i].set('displayedValue', obj.event.name);
                } else {
                    eventFilteringSelect[i].reset();
                }
            }
        }
    }

    return {
        run: run,
        getData: getData,
        setData: setData
    }
}
);
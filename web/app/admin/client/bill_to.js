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
    "dijit/form/Select",
    "dojo/i18n!app/nls/core",
    "dojo/i18n!app/nls/schedule",
    "dojo/NodeList-dom",
    "dojo/NodeList-traverse",
    "dojo/domReady!"
], function (dom, domAttr, domConstruct, on, query, aspect,
        JsonRest,
        CurrencyTextBox, ValidationTextBox, FilteringSelect, Select,
        core, schedule) {
    //"use strict";

    var dataPrototype;
    var prototypeNode, prototypeContent;
    var billToId = [], clientId = [], clientFilteringSelect = [], eventId = [], eventFilteringSelect = [], amountInput = [], commentInput = [];
    var clientStore, eventStore;
    var divIdInUse = 'transfer_bill_tos';
    var addOneMoreControl = null;

    function getDivId() {
        return divIdInUse;
    }

    function cloneNewNode() {
        prototypeContent = dataPrototype.replace(/__bill_to__/g, clientFilteringSelect.length);
        domConstruct.place(prototypeContent, prototypeNode, "after");
        billToId.push(null);
    }

    function createDijits() {
        var dijit;
        var base = prototypeNode.id + "_" + clientFilteringSelect.length + "_";
        dijit = new FilteringSelect({
            store: clientStore,
            labelAttr: "name",
            searchAttr: "name",
            placeholder: core.client,
            pageSize: 25
        }, base + "client");
        clientFilteringSelect.push(dijit);
        dijit.startup();
        dijit = new FilteringSelect({
            store: eventStore,
            labelAttr: "name",
            searchAttr: "name",
            placeholder: schedule.event,
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
        var i, item;

        for( i = 0; i < billToId.length; i++ ) {
            if( billToId[i].get("id").indexOf(id) !== -1 ) {
                id = i;
                break;
            }
        }
        billToId.splice(id, 1);
        item = clientFilteringSelect.splice(id, 1);
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

        prototypeNode = dom.byId(getDivId());
        dataPrototype = domAttr.get(prototypeNode, "data-prototype");
        prototypeContent = dataPrototype.replace(/__bill_to__/g, '0');

        domConstruct.place(prototypeContent, prototypeNode, "after");

        clientStore = new JsonRest({
            target: '/api/store/clients',
            useRangeHeaders: false,
            idProperty: 'id'});

        eventStore = new JsonRest({
            target: '/api/store/events',
            useRangeHeaders: false,
            idProperty: 'id'});

        aspect.before(eventStore, "query", function (args) {
            console.log('ha');
            console.log(this,args);
        });

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
            destroyRow(id, targetParent.parentNode);
        });
    }

    function getData() {
        var i, l = itemId.length, returnData = [];
        for( i = 0; i < l; i++ ) {
            returnData.push(
                    {
                        "id": billToId[i],
                        "client": clientFilteringSelect[i].get('value'),
                        "event": eventFilteringSelect[i].get('value'),
                        "amount": parseFloat(amountInput.get("value")),
                        "comment": commentInput[i].get('value')
                    });
        }
        return returnData;
    }

    function setData(items) {
        var i, l, obj, nodes;

        nodes = query(".form-row.transfer-bill-to", "bill-tos");
        nodes.forEach(function (node, index) {
            if( index !== 0 ) {
                destroyRow(index, node);
            }
        });

        if( typeof items === "object" && items !== null && l > 0 ) {
            l = items.length;
            for( i = 0; i < l; i++ ) {
                cloneNewNode();
                createDijits();
                obj = items[i];
                billToId[i] = obj.id;
                clientFilteringSelect[i].set('displayedValue', obj.client);
                eventFilteringSelect[i].set('value', obj.event);
                amountInput[i].set("value", obj.amount);
                commentInput[i].set('value', obj.comment);
            }
        } else {
            billToId[0] = null;
            clientFilteringSelect[0].set('displayedValue', '');
            eventFilteringSelect[0].set('value', '');
            amountInput[0].set("value", null);
            commentInput[0].set('value', '');
        }
    }

    return {
        run: run,
        getData: getData,
        setData: setData
    }
}
);
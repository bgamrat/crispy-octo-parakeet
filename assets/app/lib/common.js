define([
    "dojo/request/xhr",
    "dijit/Dialog",
    "dijit/ConfirmDialog",
    "dojo/i18n!app/nls/core",
    "dojo/domReady!"
], function (xhr, Dialog, ConfirmDialog, core) {
    "use strict";

    var confirmDialog = new ConfirmDialog({
        title: core.confirm
    });
    confirmDialog.startup();

    var errorDialog = new Dialog({
        title: core.error
    });
    errorDialog.startup();

    function confirmAction(text, callbackFn) {
        confirmDialog.set("execute", callbackFn);
        confirmDialog.set("content", text);
        confirmDialog.show();
    }
    function isEmpty(obj) {
        var prop;
        // Thanks to: http://stackoverflow.com/a/32108184/2182349
        if( typeof obj.keys !== "undefined" ) {
            return obj.keys({}).length !== 0;
        } else {
            for( prop in obj ) {
                if( obj.hasOwnProperty(prop) )
                    return false;
            }
            return true;
        }
    }

    function checkForFormErrors(data) {
        var i, l, errorText = "";
        if( typeof data !== "undefined" && typeof data[0] !== "undefined" ) {
            l = data.length;
            for( i = 0; i < l; i++ ) {
                errorText += data[i].message + "<br>";
            }
            errorDialog.set("title", core.error);
            errorDialog.set("content", errorText);
            errorDialog.show();
            return true;
        }
        return false;
    }

    function textError(msg) {
        errorDialog.set("content", msg);
        errorDialog.show();
    }

    function xhrError(err) {
        var errObj = JSON.parse(err.response.text);
        if( typeof errObj.error !== "undefined" && errObj.error !== null ) {
            if( typeof errObj.error.message !== "undefined" ) {
                errorDialog.set("title", errObj.error.message);
            }
            if( typeof errObj.error.exception !== "undefined" ) {
                errorDialog.set("content", errObj.error.exception[0].message.replace("\n", "<br>"));
            }
        } else {
            if( typeof errObj.message !== "undefined" ) {
                errorDialog.set("content", errObj.message);
            } else {
                errorDialog.set("content", errObj);
            }
        }
        errorDialog.show();
    }

    function pageReady() {
        if( document.querySelector(".loading") !== null ) {
            document.querySelector(".loading").classList.add("hidden");
        }
        var i, nodes = document.querySelectorAll(".hide-on-load");
        for( i = 0; i < nodes.length; i++ ) {
            nodes[i].classList.remove("hide-on-load");
        }

    }

    function formatDate(value, unix) {
        var date = new Date(), year, month, day;
        if( typeof unix === "undefined" ) {
            unix = true;
        }
        if( unix === true ) {
            value *= 1000;
        }
        date.setTime(value);
        year = date.getFullYear();
        month = date.getMonth() + 1;
        if( month < 10 ) {
            month = '0' + month.toString();
        }
        day = date.getDate();
        if( day < 10 ) {
            day = '0' + day.toString();
        }
        return year + '-' + month + '-' + day;
    }

    function addTimeToDate(date, time) {
        if( date instanceof Date ) {
            date.setHours(time.getHours());
            date.setMinutes(time.getMinutes());
            date.setSeconds(time.getSeconds());
        }
    }

    function showHistory(historyContentPane, historyLog) {
        var i, date, dateText, d, dataText, h, historyHtml;
        date = new Date();
        historyHtml = "<ul>";
        for( i = 0; i < historyLog.length; i++ ) {
            h = historyLog[i];
            if( typeof h.username === "undefined" || h.username === null ) {
                h.username = '';
            }
            dateText = h.timestamp;
            dataText = [];
            for( d in h.data ) {
                dataText.push(d + ' set to ' + h.data[d]);
            }
            historyHtml += "<li>" + dateText + " " + h.username + " " + h.action + " " + dataText.join(', ') +
                    "</li>";
        }
        historyHtml += "</ul>";
        if( historyHtml.length > 0 ) {
            historyContentPane.set("content", historyHtml);
        } else {
            historyContentPane.set("content", "");
        }
    }

    var addressTypes = [];
    function getAddressTypes() {
        return xhr.get("/api/store/addresstypes", {
            handleAs: "json"
        }).then(function (res) {
            var i, l;
            l = res.length;
            for( i = 0; i < l; i++ ) {
                addressTypes[res[i].id] = res[i]["type"];
            }
        })
    }

    var contactTypes = [];
    function getContactTypes() {
        return xhr.get("/api/store/contacttypes", {
            handleAs: "json"
        }).then(function (res) {
            var i, l;
            l = res.length;
            for( i = 0; i < l; i++ ) {
                contactTypes[res[i].id] = res[i]["type"];
            }
        })
    }

    var emailTypes = [];
    function getEmailTypes() {
        return xhr.get("/api/store/emailtypes", {
            handleAs: "json"
        }).then(function (res) {
            var i, l;
            l = res.length;
            for( i = 0; i < l; i++ ) {
                emailTypes[res[i].id] = res[i]["type"];
            }
        })
    }

    var personTypes = [];
    function  getPersonTypes() {
        return xhr.get("/api/store/persontypes", {
            handleAs: "json"
        }).then(function (res) {
            var i, l;
            l = res.length;
            for( i = 0; i < l; i++ ) {
                personTypes[res[i].id] = res[i]["type"];
            }
        })
    }

    var phoneTypes = [];
    function getPhoneTypes() {
        return xhr.get("/api/store/phonetypes", {
            handleAs: "json"
        }).then(function (res) {
            var i, l;
            l = res.length;
            for( i = 0; i < l; i++ ) {
                phoneTypes[res[i].id] = res[i]["type"];
            }
        })
    }

    function constructContactName(obj) {
        var contactName = [];
        if( typeof obj.firstname !== "undefined" )
        {
            contactName.push(obj.firstname);
        }
        if( typeof obj.middlename !== "undefined" )
        {
            contactName.push(obj.middlename);
        }
        if( typeof obj.lastname !== "undefined" )
        {
            contactName.push(obj.lastname);
        }
        if( contactName.length === 0 )
        {
            if( typeof obj.title !== "undefined" )
            {
                contactName.push(obj.title);
            }
        }
        return contactName.join(' ');
    }

    return {
        addressTypes: addressTypes,
        addTimeToDate: addTimeToDate,
        confirmAction: confirmAction,
        contactTypes: contactTypes,
        emailTypes: emailTypes,
        formatDate: formatDate,
        getAddressTypes: getAddressTypes,
        getContactTypes: getContactTypes,
        getEmailTypes: getEmailTypes,
        getPersonTypes: getPersonTypes,
        getPhoneTypes: getPhoneTypes,
        isEmpty: isEmpty,
        pageReady: pageReady,
        personTypes: personTypes,
        phoneTypes: phoneTypes,
        showHistory: showHistory,
        checkForFormErrors: checkForFormErrors,
        textError: textError,
        xhrError: xhrError,
        constant: {
            MAX_PHONE_NUMBERS: 5,
            MAX_ADDRESSES: 3,
            MAX_CONTACTS: 5,
            MAX_BRANDS: 20,
            MAX_CONTRACTS: 50
        }
    };
});
//# sourceURL=common.js
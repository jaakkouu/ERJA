$(function() {
    getUserGroups();
    $("#removeTaskImage").on("click", function() {
        $(this).toggle();
        $("#previewTaskImage").attr("src", "").toggle();
        $("#addTaskImage").toggle();
    });
    $("#addTaskImage").on("click", function() {
        openFilePicker('taskimage');
        $(this).toggle();
        $("#removeTaskImage").toggle();
    });

});

function taskImageSuccess(imageData) {
    $("#previewTaskImage").attr("src", imageData).fadeIn();
}

function createTask() {
    authenticate();
    var task_group = $("#task_group").val(),
        task_name = $("#task_name").val(),
        task_desc = $("#task_description").val(),
        task_amount = $("#task_amount").data("taskamount"),
        task_enddate = getDateFormat('enddate'),
        task_startdate = getDateFormat('startdate'),
        task_image;

    if ($("#previewTaskImage").attr("src").length) {
        var myRandomId = makeid();
        saveTaskImage(myRandomId);
        task_image = myRandomId + "image.jpg";
    } else {
        task_image = null;
    }

    $.post(ajaxUrl, {
        function: "task",
        action: "create",
        group_id: task_group,
        name: task_name,
        image: task_image,
        desc: task_desc,
        amount: task_amount,
        startdate: task_startdate,
        enddate: task_enddate
    }, redirect('admin.html'));
}

function saveTaskImage(randomId) {
    var imageURI = $("#previewTaskImage").attr("src"),
        uploadOptions = {
            fileKey: "file",
            mimeType: "image/jpeg",
            params: {
                function: "task",
                action: "uploadTaskImage",
                myRandomId: randomId
            },
            chunkedMode: false
        };
    var ft = new FileTransfer();
    ft.upload(imageURI, encodeURI(server("erja")), function(result) {

    }, function(error) {
        alert("Palvelimeen ei saatu yhteyttä");
    }, uploadOptions);
}

function makeid() {
    var text = "",
        possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 5; i++) {
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    }
    return text;
}

function getUserGroups() {
    authenticate();
    $.get(ajaxUrl, {
        function: "user",
        action: "getGroups",
        user_id: getStringFromLocalStorage('account', 'id')
    }, function(data) {
        groups = JSON.parse(data);
        if (groups.length) {
            $("#groupSelect").show();
            for (var i = 0; i < groups.length; i++) {
                $("#task_group").attr("onchange", "makeFlatPickr(this.value)").append(
                    $("<option />").val(groups[i].id).text(groups[i].groups_name)
                )
            }
        } else {
            $("#page-task #noGroups").show();
        }
    });
}

function getTaskDates(group_id) {
    return $.get(ajaxUrl, {
        function: "task",
        action: "getTaskDates",
        group_id: group_id
    });
}

function makeFlatPickr(group_id) {
    var disabledDates = getTaskDates(group_id);
    disabledDates.done(function(data) {
        flatpickrOptions.disable = JSON.parse(data);
        $("#page-task form").css("display", "grid");
        $("#task_date").flatpickr(flatpickrOptions);
    });
}

function getDateFormat(type) {
    var date = $("#task_date").val();
    date = type == "startdate" ? date.substr(0, date.indexOf('-')) : date.split('-').pop();
    date = date.trim();
    date = date.slice(0, 10).split('.');
    date = date[2] + '-' + date[1] + '-' + date[0];
    return date;
}

var flatpickrOptions = {
    locale: {
        firstDayOfWeek: 1,
        rangeSeparator: " - ",
        weekdays: {
            shorthand: ["Su", "Ma", "Ti", "Ke", "To", "Pe", "La"],
            longhand: [
                "Sunnuntai",
                "Maanantai",
                "Tiistai",
                "Keskiviikko",
                "Torstai",
                "Perjantai",
                "Lauantai",
            ],
        },
        months: {
            shorthand: [
                "Tammi",
                "Helmi",
                "Maalis",
                "Huhti",
                "Touko",
                "Kesä",
                "Heinä",
                "Elo",
                "Syys",
                "Loka",
                "Marras",
                "Joulu",
            ],
            longhand: [
                "Tammikuu",
                "Helmikuu",
                "Maaliskuu",
                "Huhtikuu",
                "Toukokuu",
                "Kesäkuu",
                "Heinäkuu",
                "Elokuu",
                "Syyskuu",
                "Lokakuu",
                "Marraskuu",
                "Joulukuu",
            ],
        },
    },
    dateFormat: "d.m.Y",
    minDate: "today",
    mode: "range"
}
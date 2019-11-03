var isUserInGroupTimer,
    waitingGroupResponse = window.localStorage.getItem("waitingGroupResponse");

$(function() {

    var $currentPageBtn = $("a[data-page='current_event']"),
        $pastPageBtn = $("a[data-page='past_events']"),
        getGroupStatus = isUserInGroup();

    /*
    if (getStringFromLocalStorage("account", "profile_image").length) {
        $(".menuarea .navigation_avatar").attr("src", getStringFromLocalStorage("account", "profile_image"));
    }*/

    getGroupStatus.done(function(data) {
        if (data == 1) {
            //User is in group
            $("header nav ul").show();
            $currentPageBtn.trigger("click");
            $('.innerWrapper').addClass('hasTabs');
        } else {
            //User is not in group
            if (waitingGroupResponse === null) {
                $("header nav ul").hide();
                $("#page-no_group").show();
            } else {
                $loader.children().children("p").text("Odotetaan vastausta");
                $loader.show();
                isUserInGroupTimer = setInterval(function() {
                    var getGroupStatus = isUserInGroup();
                    getGroupStatus.done(function(data) {
                        if (data == 1) {
                            $("#page-no_group").hide();
                            $("header nav ul").show();
                            clearInterval(isUserInGroupTimer);
                            window.localStorage.removeItem("waitingGroupResponse");
                            $loader.children().children("p").text("Pyyntö hyväksytty!");
                            setTimeout(function() {
                                $loader.fadeOut();
                                $currentPageBtn.trigger("click");
                            }, 8000);
                        } 
                    });
                }, 15000);
            }
        }
    });
    
    $currentPageBtn.on("click", function() {
        var currentTask = getTasks("current");
        currentTask.done(function(data) {
            setCurrentTask(JSON.parse(data));
        });
    });
    
    $pastPageBtn.on("click", function() {
        var pastTasks = getTasks("past");
        pastTasks.done(function(data) {
            setPastTasks(JSON.parse(data));
        });
    });

});

function setPastTasks(tasks) {
    var container = $("#list_past_tasks");
    container.html("");
    if (tasks.length == 0) {
        container.text("Sinulla ei ole menneitä tehtäviä");
    } else {
        for (var i = 0; i < tasks.length; i++) {
            var startDay = moment(tasks[i].startday),
                endDate = moment(tasks[i].enddate),
                timeLeft = endDate - startDay,
                hours = moment.duration(timeLeft).hours(),
                days = Math.trunc(moment.duration(timeLeft).asDays()),
                desc = $("<i />").addClass("taskCompletionStatus"),
                title = $("<h2 />").addClass("title").text(tasks[i].task_name),
                controls = $("<div />").addClass("controls").append(
                    $("<button />").addClass("btn btn-text-center btn-center").attr("onclick", "window.localStorage.setItem('selected_task', " + JSON.stringify(JSON.stringify(tasks[i])) + "); getTaskAnswers(); openModal('.modal_showAnswer')").text("Katso vastaukset")
                );
            tasks[i].completed ? desc.addClass("completed fa fa-thumbs-up").css("color", "green") : desc.addClass("incompleted fa fa-thumbs-down").css("color", "red");
            title.prepend(desc);
            container.append(
                $("<div />").attr("id", "task-" + tasks[i].id).addClass("task task_past").append(
                    title, controls
                )
            )
        }
    }
}

function completeTask() {
    $.post(ajaxUrl, {
        function: "task",
        action: "completeTask",
        user_id: getStringFromLocalStorage("account", "id"),
        message: $("#completeTaskAnswer").val(),
        feedback: $("#completeTaskFeedback").val(),
        visibility: $("input[name='answer_visibility']:checked").val(),
        task_id: getStringFromLocalStorage("selected_task", "id")
    }, function (data) {
        window.localStorage.removeItem('savedTaskAnswer');
        if (data == "completed") {
            redirect("current");
        }
    })
}

function getTasks(type) {
    return $.get(ajaxUrl, {
        function: "task",
        action: "getTask",
        type: type,
        user_id: getStringFromLocalStorage("account", "id")
    });
}

function getSavedTaskAnswer() {
    var savedTaskAnswer = window.localStorage.getItem("savedTaskAnswer");
    if (savedTaskAnswer !== null) {
        savedTaskAnswer = JSON.parse(savedTaskAnswer);
        $("#completeTaskAnswer").val(savedTaskAnswer.answer);
        $("#completeTaskFeedback").val(savedTaskAnswer.feedback);
    }
}

function sendGroupRequest() {
    var groupName = $("#requestGroupName").val(),
        groupKey = $("#requestGroupKey").val();
    $.post(ajaxUrl, {
        function: "group",
        action: "sendGroupRequest",
        groups_name: groupName,
        groups_password: groupKey,
        user_id: getStringFromLocalStorage("account", "id")
    }, function (data) {
        switch (data) {
            case 'wrongpass':
                alert("Ryhmän nimi tai avain on väärin");
                break;
            case 'nogroup':
                alert("Haulla ei löytynyt ryhmää");
                break;
            case 'ok':
                window.localStorage.setItem("waitingGroupResponse", groupName);
                redirect("current");
                break;
            case 'requestexists':
                window.localStorage.setItem("waitingGroupResponse", groupName);
                redirect("current");
                break;
        }
    });
}


function setCurrentTask(task) {
    var container = $("#list_current_tasks");
    container.html("");
    if (task.length == 0) {
        container.text("Ei uusia tehtäviä");
    } else if (task.completed) {
        container.append(
            $("<div />").addClass("goodjobContainer").append(
                $("<img />").attr("src", "img/goodjob.png"),
                $("<h2 />").text("Olet jo suorittanut tehtävän!"),
                $("<p />").text("Seuraava tehtävä julkaistaan pian!"),
                $("<button />").addClass("btn btn-text-center btn-center").attr("onclick", "window.localStorage.setItem('selected_task', " + JSON.stringify(JSON.stringify(task)) + "); getTaskAnswers(); openModal('.modal_showAnswer')").text("Katso vastaukset")
            )
        );
    } else {
        if (task[0].image !== null) {
            var getImage = getTaskImage(task[0].image);
        }
        var endDate = moment(task[0].enddate),
            title = $("<h1 />").addClass("title").text(task[0].task_name),
            timer = $("<p />").addClass("timer").append(
                $("<i />").addClass("fa fa-clock-o fa-fw"),
                $("<span />").text(moment(endDate).toNow(true) + " jäljellä")
            ),
            imageContainer = $("<div />").css('display', 'none').addClass("imageContainer").append(
                $("<img />").addClass("image")
            ),
            desc = $("<p />").css("white-space", "pre-line").addClass("desc").text(task[0].task_desc),
            controls = $("<div />").addClass("controls").append(
                $("<button />").addClass("btn btn-stump btn-text-center btn-center").attr("onclick", "window.localStorage.setItem('selected_task', " + JSON.stringify(JSON.stringify(task[0])) + "); getSavedTaskAnswer(); openModal('.modal_answer'); ").text("Vastaa"),
                $("<button />").addClass("btn btn-text-center btn-center").attr("onclick", "window.localStorage.setItem('selected_task', " + JSON.stringify(JSON.stringify(task[0])) + "); getTaskAnswers(); openModal('.modal_showAnswer')").text("Katso vastaukset")
            );
        container.append(
            $("<div />").attr("id", "task-" + task[0].id).addClass("task task_current").append(
                title, imageContainer, timer, desc, controls
            )
        )
        if (task[0].image !== null) {
            getImage.done(function(data) {
                return imageContainer.children().attr("src", data).css("display", "block").fadeIn();
            });
        } else {
            $("#task-" + task[0].id).find(".imageContainer").remove();
        }
    }
}
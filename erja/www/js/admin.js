function groupAction(page, title) {
    var selected_group = getStringFromLocalStorage('selected_group', 'id');
    switch (page) {
        case 'listGroupRequests':
            getRequests(selected_group);
            break;
        case 'listGroupUsers':
            var users = getGroupUsers(selected_group);
            users.done(function (data) {
                var data = JSON.parse(data);
                if (data.length) {
                    $(".modal_page[data-mdpage=" + page + "] .modal_page_content").append(
                        $("<div />").addClass("listItems")
                    )
                    for (var i = 0; i < data.length; i++) {
                        $(".modal_page[data-mdpage=" + page + "] .modal_page_content .listItems").append(
                            $("<div />").append(
                                $("<span />").text(data[i].email)
                            )
                        )
                    }
                } else {
                    $(".modal_page[data-mdpage=" + page + "] .modal_page_content").append(
                        $("<p />").text("Sinun ryhmässä ei ole jäseniä")
                    )
                }
            });
            break;
        case 'removeGroupUsers':
            var users = getGroupUsers(selected_group);
            users.done(function (data) {
                var data = JSON.parse(data);
                if (data.length) {
                    $(".modal_page[data-mdpage=" + page + "] .modal_page_content").append(
                        $("<div />").addClass("listItems listActions")
                    )
                    for (var i = 0; i < data.length; i++) {
                        $(".modal_page[data-mdpage=" + page + "] .modal_page_content .listItems").append(
                            $("<div />").append(
                                $("<span />").text(data[i].email),
                                $("<i />").css({
                                    "text-align": "center",
                                    "cursor": "pointer"
                                }).attr("onclick", "removeUserFromGroup(" + data[i].id + ")").addClass("fa fa-remove lg")
                            )
                        )
                    }
                } else {
                    $(".modal_page[data-mdpage=" + page + "] .modal_page_content").append(
                        $("<p />").text("Sinun ryhmässä ei ole jäseniä")
                    )
                }
            });
            break;
        case 'listGroupReports':
            var getGroupsTasks = getGroupsTasks();
            break;
    }
    $(".modal_group_actions .modal_header").text(title);
    $('.modal_group_actions .modal_page[data-mdpage=mainmenu]').hide();
    $('.modal_group_actions .modal_page[data-mdpage=' + page + ']').fadeIn();
    $('.modal_group_actions .modal_page .modal_page_content').html("");
}

function returnGroupAction() {
    $('.modal_group_actions .modal_page').hide();
    $('.modal_group_actions .modal_page[data-mdpage=mainmenu]').fadeIn();
}

function getGroupUsers(group_id) {
    return $.get(ajaxUrl, {
        function: "group",
        action: "listUsersByGroup",
        group_id: group_id
    })
}

function setSelectedGroup(id) {
    var groups = JSON.parse(window.localStorage.getItem("groups"));
    for (i = 0; i < groups.length; i++) {
        if (groups[i].id == id) {
            var selected_group = {
                id: groups[i].id,
                title: groups[i].groups_name
            };
            window.localStorage.setItem('selected_group', JSON.stringify(selected_group));
            break;
        }
    }
}

function getMessagesByGroup(group_id) {
    return $.get(ajaxUrl, {
        function: "message",
        action: "listMessagesByGroupId",
        group_id: group_id
    });
}

function getRequests(group_id) {
    authenticate();
    $(".modal_page_content").html("");
    $.get(ajaxUrl, {
        function: "group",
        action: "getGroupRequests",
        group_id: group_id
    }, function (data) {
        var users = JSON.parse(data);
        if (users.length) {
            $(".modal_page[data-mdpage='listGroupRequests'] .modal_page_content").append(
                $("<div />").addClass("listItems listActions")
            )
            for (var i = 0; i < users.length; i++) {
                $('.modal_page[data-mdpage="listGroupRequests"] .modal_page_content .listItems').append(
                    $("<div />").append(
                        $("<span />").text(users[i].email),
                        $("<i />").attr("onclick", "acceptUserRequest(" + users[i].user_id + ")").addClass("fa fa-plus fa-lg").css("justify-self", "center")
                    )
                )
            }
        } else {
            $('.modal_page_content').append($("<p />").text("Ei kutsuja"))
        }
    })
}

function acceptUserRequest(user_id) {
    authenticate();
    $.post(ajaxUrl, {
        function: "group",
        action: "acceptRequest",
        user_id: user_id,
        group_id: getStringFromLocalStorage('selected_group', 'id')
    }, function (data) {
        if (data == "added") {
            getRequests(getStringFromLocalStorage('selected_group', 'id'));
        }
    });
}

function addUserToGroup(id) {
    authenticate();
    $.post(ajaxUrl, {
        function: "group",
        action: "addUser",
        user_id: id,
        group_id: getStringFromLocalStorage('selected_group', 'id')
    }, function (data) {
        $(event.target).parent().fadeOut().remove();
    });
}

function removeUserFromGroup(user_id) {
    $(event.target).parent().fadeOut().remove();
    authenticate();
    $.post(ajaxUrl, {
        function: "group",
        action: "removeUser",
        user_id: user_id,
        group_id: getStringFromLocalStorage('selected_group', 'id')
    });
}

function getGroups() {
    return $.get(ajaxUrl, {
        function: "user",
        action: "getGroups",
        user_id: getStringFromLocalStorage('account', 'id')
    });
}

function getGroupsTasks(group_id) {
    return $.get(ajaxUrl, {
        function: "group",
        action: "getGroupsTasks",
        group_id: group_id
    });
}

function listUsersGroups(groups) {
    $(".groups_listing_parent").css("width", $(".messages").width() + "px");
    groups.forEach(function(group) {
        $(".groups_listing_inner").append(
            $("<div />").addClass("group").css("cursor", "pointer").attr(
                "data-groupid", group.id
            ).text(group.groups_name)
        )
    });
}

function getUsersGroups() {
    return $.get(ajaxUrl, {
        function: "user",
        action: "getGroups",
        user_id: getStringFromLocalStorage('account', 'id')
    });
}

function taskDeleteButton(id) {
    return $("<span />").attr("onclick", "deleteTask(" + id + ")").addClass("removeTaskButton").append(
        $("<i />").addClass("fa fa-remove fa-fw"),
        "Poista tehtävä"
    );
}

/*
function listTask(data, id, containerType) {
    var tasks = data;
    if (tasks.length) {
        var container = $("div[data-groupid='" + id + "'] .group_tasks")
        if (containerType == "groups") {
            if (tasks.length === 1) {
                container.append(
                    $("<div />").addClass("group_task").attr("data-taskid", tasks[0].id).append(
                        $("<div />").addClass("title").append(
                            $("<span />").text(tasks[0].task_name)
                        ),
                        $("<ul />").addClass("opened").css("display", "block").append(
                            $("<li />").text(tasks[0].task_desc),
                            $("<li />").html("<i class='fa fa-calendar'></i>  " + moment(tasks[0].startdate).format('L') + " - " + moment(tasks[0].enddate).format('L')),
                            $("<li />").append(taskDeleteButton(tasks[0].id))
                        )
                    )
                )
            } else if (tasks.length >= 2) {
                for (i = 0; i < tasks.length; i++) {
                    container.append(
                        $("<div />").addClass("group_task").attr("data-taskid", tasks[i].id).append(
                            $("<div />").addClass("title").append(
                                $("<span />").text(tasks[i].task_name),
                                $("<i />").addClass("fa")
                            ),
                            $("<ul />").addClass("closed").append(
                                $("<li />").text(tasks[i].task_desc),
                                $("<li />").html("<i class='fa fa-calendar'></i>  " + moment(tasks[i].startdate).format('L') + " - " + moment(tasks[i].enddate).format('L')),
                                $("<li />").append(taskDeleteButton(tasks[i].id))
                            )
                        )
                    )
                }
            }
        } else {
            if (tasks.length === 1) {
                container.append(
                    $("<div />").addClass("group_task").attr("data-taskid", tasks[0].id).append(
                        $("<div />").addClass("title").append(
                            $("<span />").text(tasks[0].task_name),
                            $("<i />").addClass("fa fa-folder-open").css("cursor", "pointer").attr("onclick", "window.localStorage.setItem('selected_task', " + JSON.stringify(JSON.stringify(tasks[0])) + "); getTaskAnswers(); openModal('.modal_showAnswer')")
                        )
                    )
                )
            } else if (tasks.length >= 2) {
                for (i = 0; i < tasks.length; i++) {
                    container.append(
                        $("<div />").addClass("group_task").attr("data-taskid", tasks[i].id).append(
                            $("<div />").addClass("title").append(
                                $("<span />").text(tasks[i].task_name),
                                $("<i />").addClass("fa fa-folder-open").css("cursor", "pointer").attr("onclick", "window.localStorage.setItem('selected_task', " + JSON.stringify(JSON.stringify(tasks[i])) + "); getTaskAnswers(); openModal('.modal_showAnswer')")
                            )
                        )
                    )
                }
            }
        }
    }
    $("div[data-groupid='" + id + "']").parent().parent().find(".group_actions").find("i").removeClass("fa-refresh fa-spin").addClass("fa-cog");
}
*/


function listTasks($container, tasks){
    if (tasks.length > 0) {
        tasks.forEach(function(task){
            $container.append(listTaskBlock(task));
        });
    } else {
        $container.append($("<div />").text("Sinulla ei ole ryhmiä"));
    }
}

function listTaskBlock(task) {
    return $("<div />").addClass("group_task").attr("data-taskid", task.id).append(
        $("<div />").addClass("title").append(
            $("<span />").text(task.task_name)
        ),
        $("<ul />").addClass("opened").css("display", "block").append(
            $("<li />").text(task.task_desc),
            $("<li />").html("<i class='fa fa-calendar'></i>  " + moment(task.startdate).format('L') + " - " + moment(task.enddate).format('L')),
            $("<li />").append(taskDeleteButton(task.id))
        )
    );
}

function deleteTask(task_id) {
    var r = confirm("Haluatko varmasti poistaa kyseisen tehtävän?");
    if (r == true) {
        authenticate();
        $.post(ajaxUrl, {
            function: "task",
            action: "delete",
            task_id: task_id
        }, function () {
            redirect("current");
        });
    }
}

function listGroupBlock(group) {
    var $icon = $("<i />").addClass("fa fa-refresh fa-spin fa-lg").attr("onclick", "setSelectedGroup(" + group.id + "); openModal('.modal_group_actions')"),
        $row = $("<div />").addClass("group_row").attr("data-groupid", group.id).append(
        $("<div />").addClass("group_title").append($("<h3 />").text(group.groups_name)),
        $("<div />").addClass("group_actions").append($icon),
        $("<div />").addClass("group_tasks")
    );
    var gettingGroupTasks = getGroupsTasks(group.id);
    gettingGroupTasks.done(function(response) {
        $icon.removeClass('fa-spin fa-refresh').addClass('fa-cog');
        listTasks($row.find('.group_tasks'), JSON.parse(response));
    });
    return $row;
}

function listGroupCommentsBlock(comment) {
    var $row = $("<div />").addClass("list_comments").append(
        $("<div />").addClass("group_row").attr("data-groupid", groupId).append(
            $("<div />").addClass("group_title").append(
                $("<h3 />").text(groupName)
            ),
            $("<div />").addClass("group_tasks").append(
                function () {
                    getGroupsTasks(groupId, containerType);
                }
            )
        )
    );
}

function listComments(comments) {
    var $page = $('#page-groups');
    $page.html('');
    if (comments.length > 0) {
        $page.append($('<div />').addClass('list_groups'));
        var $container = $page.find('.list_groups');
        comments.forEach(function(comment){
            $container.append(listGroupCommentsBlock(comment));
        });
    } else {
        $page.append($("<div />").text("Sinulla ei ole ryhmiä"));
    }
}

function listGroups(groups) {
    var $page = $('#page-groups');
    $page.html('');
    if (groups.length > 0) {
        $page.append($('<div />').addClass('list_groups'));
        var $container = $page.find('.list_groups');
        groups.forEach(function(group){
            $container.append(listGroupBlock(group));
        });
    } else {
        $page.append($("<div />").text("Sinulla ei ole ryhmiä"));
    }
} 
    /*
        var data = JSON.parse(data);
        if (data.length) {
            listTask(data, id, containerType);
        } else {
            if (containerType == "groups") {
                $("div[data-groupid='" + id + "'] .group_tasks").append($("<span />").css("font-size", "14px").html("Ryhmällä ei ole tehtäviä. " + "<a style='color: #B10DC9' href='task.html'>Siirry tästä luomaan tehtävä</a>"))
                $("div[data-groupid='" + id + "'] .group_tasks").parent().parent().find(".group_actions").find("i").removeClass("fa-refresh fa-spin").addClass("fa-cog");
            } else {
                $("div[data-groupid='" + id + "']").hide();
            }
        }
    */

    
moment.locale("fi");
var $loader = $('.loading');

function init() {
    document.addEventListener("deviceready", onDeviceReady, false);
}

function onDeviceReady() {
    document.addEventListener("backbutton", function (e) {
        e.preventDefault;
    }, false);
    if (window.localStorage.getItem("account") === null) {
        logout();
    } else {
        /*
        var notificationOpenedCallback = function(jsonData) {
            console.log('notificationOpenedCallback: ' + JSON.stringify(jsonData));
        };
        window.plugins.OneSignal.startInit("794eb6bc-6a5a-4073-990d-a98c59b1bda8").handleNotificationOpened(notificationOpenedCallback).endInit();
        window.plugins.OneSignal.getPermissionSubscriptionState(function(status) {
            saveOneSignalId(status.subscriptionStatus.userId);
        });
        */
    }
}

function saveOneSignalId(userId) {
    $.post(ajaxUrl, {
        function: "user",
        action: "saveOneSignalId",
        user_id: getStringFromLocalStorage("account", "id"),
        oneSignalId: userId
    });
}

$(function () {
    var timer;
    $(document).bind("ajaxSend", function () {
        timer && clearTimeout(timer);
        timer = setTimeout(function () {
            $loader.fadeIn();
        }, 1500);
    }).bind("ajaxComplete", function () {
        clearTimeout(timer);
        $loader.fadeOut();
    });
    $(document).on("click", '.tab', function () {
        $(".page").hide();
        $('.tab').removeClass('active-tab');
        $(this).addClass("active-tab");
        var page = $(this).data("page");
        $("#page-" + page).fadeIn();
    });
    $(".radioInput").click(function () {
        var label = $(this),
            parent = label.parent();
        parent.children().removeClass("selected");
        label.addClass("selected");
    });
});


function getUserImage(user_id) {
    return $.get(ajaxUrl, {
        function: "user",
        action: "getUserImage",
        user_id: user_id
    });
}

function getGroupLeaderImage(group_id) {
    return $.get(ajaxUrl, {
        function: "group",
        action: "getGroupLeaderImage",
        group_id: group_id
    });
}



/*  Camera */
function takePhoto(selection) {
    var options = {
        destinationType: Camera.DestinationType.FILE_URI,
        sourceType: Camera.PictureSourceType.CAMERA,
        correctOrientation: true,
        cameraDirection: Camera.Direction.FRONT
    }
    navigator.camera.getPicture(profileImageSuccess, cameraError, options);
}

function openFilePicker(selection) {
    var options = {
        destinationType: Camera.DestinationType.FILE_URI,
        sourceType: Camera.PictureSourceType.SAVEDPHOTOALBUM
    }
    navigator.camera.getPicture(taskImageSuccess, cameraError, options);
}

function profileImageSuccess(imageData) {
    var uploadOptions = {
        fileKey: "file",
        fileName: imageData.substr(imageData.lastIndexOf('/') + 1),
        mimeType: "image/jpeg",
        params: {
            function: "user",
            action: "uploadUserImage",
            user_id: getStringFromLocalStorage("account", "id")
        },
        chunkedMode: false
    };
    var ft = new FileTransfer();
    ft.upload(imageData, encodeURI(server("erja")), function (result) {
        setStringToLocalStorage("account", "profile_image", imageData);
        $("#avatar_area").attr("src", getStringFromLocalStorage("account", "profile_image"));
    }, function (error) {
        console.debug("Unable to upload picture: " + error, "app");
    }, uploadOptions);
}

function cameraError(error) {
    console.debug("Unable to obtain picture: " + error, "app");
}

/* End of Camera */

function saveTaskAnswer() {
    window.localStorage.setItem("savedTaskAnswer", JSON.stringify({
        "answer": $("#completeTaskAnswer").val(),
        "feedback": $("#completeTaskFeedback").val()
    }));
}


function getTaskAnswers() {
    $("#taskAnswers").html("");
    $.get(ajaxUrl, {
        function: "task",
        action: "getTaskAnswers",
        user_id: getStringFromLocalStorage("account", "id"),
        task_id: getStringFromLocalStorage("selected_task", "id")
    }, function (data) {
        var answers = JSON.parse(data),
            limit = 86,
            text;
        for (var i = 0; i < answers.length; i++) {
            var answer = answers[i].answer,
                classes = answers[i].user_id != getStringFromLocalStorage("account", "id") ? 'answer' : 'answer youranswer',
                likes = answers[i].likes == 1 ? answers[i].likes + " tykkäys" : answers[i].likes + " tykkäystä";
            if (answer.length > limit) {
                var firstPart = answer.substr(0, limit),
                    lastPart = answer.substr(limit, answer.length - limit),
                    text = $("<div />").append(
                        $("<p />").append(firstPart,
                            $("<a />").attr("href", "javascript:void(0);").attr("onclick", "showMore()").text(" Näytä lisää ").addClass("read-more-show"),
                            $("<span />").addClass("read-more-content").css("display", "none").append(lastPart,
                                $("<a />").attr("href", "javascript:void(0);").attr("onclick", "showLess()").text(" Näytä vähemmän ").addClass("read-more-hide")
                            )
                        )
                    );
            } else {
                text = $("<span />").html(answer);
            }
            $("#taskAnswers").append(
                $("<div />").addClass(classes).append(text,
                    $("<div />").addClass("actions").append(
                        $("<span />").css("padding", "5px 10px").attr("id", "answerCount-" + answers[i].id).text(likes), (
                            answers[i].likebutton ? addLikeButton(answers[i].id) : ""
                        )
                    )
                )
            )
        }
    })
}

function addLikeButton(id) {
    return $("<span />").attr({
        "id": "answerSubmitButton-" + id,
        "onclick": "submitLike(" + id + ")"
    }).addClass("likeButton").append(
        $("<i />").addClass("fa fa-thumbs-o-up")
    )
}

function submitLike(id) {
    $("#answerSubmitButton-" + id).hide();
    $.post(ajaxUrl, {
        function: "task",
        action: "submitLike",
        answer_id: id,
        user_id: getStringFromLocalStorage("account", "id")
    }, function (data) {
        $("#answerCount-" + id).text(data == 1 ? data + ' tykkäys' : data + ' tykkäystä');
    })
}

function showMore() {
    $(event.target).next('.read-more-content').toggle();
    $(event.target).toggle();
}

function showLess() {
    $(event.target).parent().toggle();
    $(event.target).parent().prev().toggle();
}


function getTaskImage(imagePath) {
    return $.get(ajaxUrl, {
        function: "task",
        action: "getTaskImage",
        imagePath: imagePath
    })
}

//checking if user is admin
function authenticate() {
    $.get(ajaxUrl, {
        function: "user",
        action: "getRole",
        user_id: getStringFromLocalStorage('account', 'id')
    }, function (data) {
        if (data != 2) {
            logout();
        }
    });
}

function openReply(message_id) {
    $(".replyMessage").fadeIn();
    $(".messageContainerActions").css("margin-bottom", "80px");
    $(".modal_message #reply").attr("onclick", "submitReply(" + message_id + ")").html("<i class='fa fa-arrow-right fa-fg'></i>")
    scrollDown(".modal .innerModal");
}

function openMessage(item) {
    var message = JSON.parse(item.message),
        replies = getReplies(item.id);
    message.id = item.id;
    replies.done(function (data) {
        var data = JSON.parse(data);
        setMessageHeaders(message);
        openModal('.modal_message');
        if (data.length) {
            setReplies(data);
        }
    });
}

function setMessageHeaders(message) {
    $("#messageContainerMessages").html("").append(
        $("<div />").addClass("userMessage").attr("id", "userMessage-0").append(
            $("<div />").addClass("title").text(message.title),
            $("<div />").addClass("message").text(message.message)
        )
    );
    $(".modal_message #reply").attr("onclick", "openReply(" + message.id + ")");
}

function setReplies(replies) {
    for (var i = 0; i < replies.length; i++) {
        setReply(replies[i]);
    }
    scrollDown(".modal .innerModal");
}

function setReply(reply) {
    $("#messageContainerMessages").append(
        $("<div />").addClass("userMessage").attr("id", "userMessage-" + reply.id).append(
            $("<div />").addClass("message").text(reply.message),
            $("<div />").addClass("author").append(
                $("<div />").addClass("name").text(reply.fullname.firstname + " " + reply.fullname.lastname),
                $("<div />").addClass("timestamp").text(moment(reply.posted).format("L"))
            )
        )
    )

}

function getReplies(message_id) {
    return $.get(ajaxUrl, {
        function: "message",
        action: "getReplies",
        message_id: message_id
    });
}

function submitReply(message_id) {
    if ($(".replyMessage").val() != "") {
        $.get(ajaxUrl, {
            function: "message",
            action: "reply",
            message_id: message_id,
            user_id: getStringFromLocalStorage("account", "id"),
            message: $(".replyMessage").val()
        }, function (data) {
            setReply(JSON.parse(data));
            $("#replyMessage").hide().val("");
            $(".modal_message #reply").attr("onclick", "openReply(" + message_id + ")").html('<i class="fa fa-commenting-o fa-lg"></i>');
            scrollDown(".modal .innerModal");
        })
    }
}

function isUserInGroup() {
    return $.get(ajaxUrl, {
        function: "user",
        action: "isUserInGroup",
        user_id: getStringFromLocalStorage("account", "id")
    })
}

function setVisited(message_id) {
    $.get(ajaxUrl, {
        function: "message",
        action: "setVisited",
        message_id: message_id
    });
}

function setUnvisited(message_id) {
    $.ajax(ajaxUrl, {
        function: "message",
        action: "setUnvisited",
        message_id: message_id
    })
}

function getGroupNameByUserId(id) {
    return $.get(ajaxUrl, {
        function: "group",
        action: "getGroupNameByUserId",
        user_id: id
    });
}

function closeModal(modal) {
    $(modal).animate({
        "right": "100%"
    }, 300, function () {
        $(modal).hide().css("right", "initial");
    });
}

function openModal(modal) {
    $(modal).fadeIn();
    $(modal).css("right", "100%").animate({
        "right": 0
    }, 300);
}

function scrollDown(element) {
    $(element).animate({
        scrollTop: $(element)[0].scrollHeight
    }, 1000);
}

function registerSuccess(email) {
    window.localStorage.setItem('preEmail', email);
    navigator.notification.alert(
        "Rekisteröinti onnistui. Käyttäjänimesi on lähetetty sähköpostiisi",
        redirect('activation.html'),
        'Sovellusilmoitus',
        'Jatka'
    );
}

function registerFailed(data) {
    var errorMsg = "";
    if (data == "inuse") {
        errorMsg = "Sähköposti on jo käytössä";
        errorType = "Sovellusilmoitus";
    }
    navigator.notification.alert(errorMsg, redirect('register.html'), errorType, 'OK');
}

function setNavigation() {
    $("#menu ul").html("");
    var pagelist = JSON.parse(window.localStorage.getItem('pagelist'));
    for (i = 0; i < pagelist.length; i++) {
        $("#menu ul").append($("<li />").append($("<a />").attr({
            title: pagelist[i].title,
            href: pagelist[i].uri,
            onclick: pagelist[i].onclick
        }).append($("<i />").addClass("fa " + pagelist[i].class)).append($("<span />").text(pagelist[i].text))))
    }
}

function getStringFromLocalStorage(item, value) {
    var data = window.localStorage.getItem(item);
    data = JSON.parse(data);
    var result = data[value];
    return result;
}

function setStringToLocalStorage(item, column, value) {
    var data = JSON.parse(window.localStorage.getItem(item));
    data[column] = value;
    window.localStorage.setItem(item, JSON.stringify(data));
}

function redirect(data) {
    data == "current" ? location.reload() : window.location = data;
}

function getMessagesByUserId(global) {
    return $.get(ajaxUrl, {
        function: "user",
        action: "getMessages",
        user_id: getStringFromLocalStorage("account", "id")
    });
}

function setMessages(messages) {
    $("#page-messages").show();
    for (var i = 0; i < messages.length; i++) {
        $("#messages").append($("<div />").css("display", "none").addClass(function () {
            if (messages[i].visited === "1") {
                return "message visited";
            } else {
                return "message";
            }
        }).attr({
            "id": "message-" + messages[i].id,
            "onclick": "openMessage(" + JSON.stringify(messages[i]) + ")"
        }).append(
            $("<div />").attr("id", "userImage-" + i).addClass("userImage").append(
                $("<i />").addClass("fa fa-comments-o fa-2x")),
            $("<div />").addClass("userMessage").append(
                $("<span />").text(JSON.parse(messages[i].message).title)),
            $("<div />").addClass("userMeta").append(
                $("<span> /").text("Viesti lähetty: " + moment(messages[i].posted).format('L'))
            ),
        ))
    }
    var delay_time = 0;
    $("#messages .message").each(function () {
        $(this).delay(delay_time).fadeIn();
        delay_time += 100;
    });
}

function logout() {
    window.localStorage.clear();
    redirect('login.html');
}

function nav(func) {
    if (func == "open") {
        setNavigation();
        $("body").css("overflow", "hidden");
        $("#menu").animate({
            "width": "270px"
        }, 300);
        $(".wrapper").append($("<div />").addClass("fade").attr("onclick", "nav()")).css('overflow', 'hidden');
        return false;
    } else {
        $("body").css("overflow", "initial");
        $("#menu").animate({
            "width": "0"
        }, 300);
        $('.wrapper').css('overflow', 'auto');
        $(".fade").fadeOut().remove();
        return false;
    }
}
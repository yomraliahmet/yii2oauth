jQuery.expr[':'].Contains = function(a, i, m) {
    return jQuery(a).text().toUpperCase()
        .indexOf(m[3].toUpperCase()) >= 0;
};

$(function(){
    $(".alert-content").prepend(
        "<div class=\"alert alert-warning\">Mesajları görüntülemek için sol taraftan sayfa seçimi yapınız.</div>"
    );

    $("body").on("keyup", ".search-input", function(){
        var deger = $(this).val();
        if(deger)
        {
            $(".message-users-list a:not(:Contains('"+deger+"'))").slideUp();
            $(".message-users-list a:Contains(" + deger + ")").slideDown();
        }
        else
        {
            $(".message-users-list a").slideDown();
        }
        return false;
    });

});

// Sidebar collapse
function sidebarCollapse(){
    $("#sidebar").toggle(100);
    if($("#main").hasClass("col-md-8")){
        $("#main").removeClass("col-md-8").addClass("col-md-12 p-0");
    }else{
        $("#main").removeClass("col-md-12 p-0").addClass("col-md-8");
    }
}

// Sidebar collapse button click
$("a.sidebar-close-button").on("click", function(){
    sidebarCollapse();
});

// Pages link click
$(".pages-list a").on("click", function(){
    var el = $(this);
    var data = {
        "page_id" : el.data("id"),
        "token" : el.data("token")
    };
    getMessageUsers(data);

});

$("body").on("click", ".message-users-list a", function(){
    var el = $(this);
    if(el.find(".no-image").length > 0){
        $("#message-picture").html(el.find(".no-image").clone());
    }else{
        $("#message-picture").html(el.find("img").clone());
    }
    $("#name").html(el.find(".name").clone());

    var data = {
        "page_id" : el.data("page_id"),
        "token" : el.data("token")
    };
    getMessage(data);
});


// Get message users
function getMessageUsers(data)
{
    $(".messages-card").fadeOut();
    $(".alert-content .alert").remove();
    $(".spinner-loading").show();

    var url = "/site/users";
    var data = data;
    var post = $.post(url,data);
    post.done(function(xhr){
        if(xhr.users.length > 0){
            $(".message-users-list").html("");
            $.each(xhr.users,function(key,user){
                $(".message-users-list").append(renderUserComponent(user));
            });

            $(".messages-card").fadeIn();
            $(".message-users-list a").eq(0).trigger("click");
        }else{
            $(".messages-card").fadeOut();
            $(".alert-content .alert").remove();
            $(".alert-content").prepend(
                "<div class=\"alert alert-danger\">Mesaj bulunamadı!</div>"
            );
        }
        $(".spinner-loading").hide();
    });
    post.fail(function(xhr){
        console.log(xhr);
        $(".messages-card").fadeOut();
        $(".alert-content .alert").remove();
        $(".alert-content").prepend(
            "<div class=\"alert alert-danger\">Bir hata oluştu!</div>"
        );
    });

}

// Render user components
function renderUserComponent(user)
{
    var html = '<a href="#" class="list-group-item list-group-item-action border-0" data-page_id="'+ user.page_id +'" data-token="'+ user.token +'">\n';
    if(user.unread_count > 0){
        html += '<div style="margin-right: -10px;" class="badge bg-success float-right">5</div>\n';
    }
    html += '<div class="d-flex align-items-start">\n';
    if(user.user_image != null){
        html += '<img src="'+ user.user_image +'" class="rounded-circle mr-1" alt="'+ user.user_name +'" width="40" height="40">\n';
    }else{
        html += '<div class="no-image">'+ user.user_name[0] +'</div>\n';
    }
    html += '<div class="flex-grow-1 ml-3 name">\n';
    html += user.user_name +'\n';
    html += '</div>\n';
    html += '</div>\n';
    html += '</a>';

    return html;
}

// Get message
function getMessage(data)
{
    $(".chat-messages").html("");

    var url = "/site/messages";
    var data = data;
    var post = $.post(url,data);

    post.done(function(xhr){
        if(xhr.messages.length > 0){
            $.each(xhr.messages,function(key,message){
                $(".chat-messages").append(renderMessageComponent(message));
            });
        }else{
            console.log("Mesaj bulunamadı..");
        }
    });

    post.fail(function(xhr){
        console.log("Hata :");
        console.log(xhr);
    });
}

// Render message component
function renderMessageComponent(message)
{
    var _class = "chat-message-right";
    var name = $("#name").text().trim();
    var image = '<div class="no-image">'+ message.name[0] +'</div>';

    if(name == message.name){
        _class = "chat-message-left";
        image = $("#message-picture").html();
    }else{
        name = message.name;
    }

    var html = '<div class="'+ _class +' pb-4">\n' +
        '<div>\n' +
         image +'\n' +
        '</div>\n' +
        '<div class="flex-shrink-1 bg-light rounded py-2 px-3 mr-3">\n' +
        '<div class="font-weight-bold mb-1">'+ name +'</div>\n' +
         message.message +'\n' +
         '<br><small style="font-size:11px; font-style: italic;color: gray;" class="float-right">'+ message.created_time +'</small>\n' +
        '</div>\n' +
        '</div>';

    return html;

}

/* ★☆★☆★☆★☆★☆★☆★☆ 提交表单处理 START ★☆★☆★☆★☆★☆★☆★☆ */
$(document).on('click', "#submitAdd", function () {
    var $form = $(this).closest('form');
    var targetUrl = "/index/index/form_handle";
    var data = $form.serialize();



    if ($form.find('[data-name=msg_code]').length > 0 && $form.find('[data-name=msg_code]').val().trim() != $form.find('[data-code]').attr('data-code').toUpperCase()) {
        layer.msg('请填写正确的验证码', { icon: 5 },);
        return false;
    }
    if ($form.find('[name=agree]').length > 0 && $form.find('.agree .layui-form-checked').length == 0) {
        layer.msg('请勾选用户隐私协议', { icon: 5 },);
        return false;
    }
    $.ajax({
        type: 'post',
        url: targetUrl,
        cache: false,
        data: data,  //重点必须为一个变量如：data
        dataType: 'json',
        success: function (data) {
            if (data.code > 0) {
                layer.msg(data.info, {
                    icon: 6,
                    time: 3000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    //跳转页面
                    window.location.reload();
                });
            } else {
                layer.msg(data.info, {
                    icon: 5,
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    //跳转页面
                    $(".layui-layer-shade").hide();
                    $(".layui-layer-loading").hide();
                });
            }

        },
        error: function () {
            layer.msg("提交失败，请稍后再试！", {
                icon: 5,
                time: 2000 //2秒关闭（如果不配置，默认是3秒）
            }, function () {
                //跳转页面
                $(".layui-layer-shade").hide();
                $(".layui-layer-loading").hide();
            });
        }
    })

}).on('click', "#submitIn", function () {
    /* ★☆★☆★☆★☆★☆★☆★☆ 提交表单处理 END ★☆★☆★☆★☆★☆★☆★☆ */

    /* 登陆提交事件 */
    var username = $("#login_username").val();
    var password = $("#login_password").val();

    if (!username) {
        layer.msg("请输入帐号", {
            icon: 5,
            time: 1000 //2秒关闭（如果不配置，默认是3秒）
        });
        return false
    }
    if (!password) {
        layer.msg("请输入密码", {
            icon: 5,
            time: 1000 //2秒关闭（如果不配置，默认是3秒）
        });
        return false
    }

    var data = $("#logForm").serialize();

    $.ajax({
        type: 'post',
        url: "/index/Member/login",
        data: data,  //重点必须为一个变量如：data
        dataType: 'json',
        success: function (data) {
            if (data.code > 0) {
                layer.msg(data.info, {
                    icon: 6,
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    console.log(data)
                    if (data.data) {
                        //跳转页面
                        window.location.href = data.data;
                        // window.location.reload();
                    } else {
                        //跳转页面
                        window.location.href = "/Index/Member/index";
                        // window.location.reload();
                    }

                });
            } else {
                layer.msg(data.info, {
                    icon: 5,
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    return false
                });
            }

        },
        error: function () {
            layer.msg("登陆失败，请稍后再试!", {
                icon: 5,
                time: 2000 //2秒关闭（如果不配置，默认是3秒）
            }, function () {
                return false
            });
        }
    })
}).on('click', "#submitToReg", function () {


    /* 用户注册提交事件 */
    //判断帐号是否为正确手机号
    var phone = $("input[name='username']").val();
    var flag = false;
    var message = "";
    var myreg = /^(((13[0-9]{1})|(14[0-9]{1})|(17[0]{1})|(15[0-3]{1})|(15[5-9]{1})|(18[0-9]{1}))+\d{8})$/;
    if (phone == '') {
        message = "手机号码不能为空！";
    } else if (phone.length != 11) {
        message = "请输入有效的手机号码！1";
    } else if (!myreg.test(phone)) {
        message = "请输入有效的手机号码！2";
    } else {
        flag = true;
    }
    if (!flag) {
        //提示错误效果
        layer.msg(message, {
            icon: 5,
            time: 1000 //2秒关闭（如果不配置，默认是3秒）
        });
        return false
    }

    var username = $("#reg_username").val();
    var phone = $("input[name='phone']").val();
    var nickname = $("input[name='nickname']").val();
    var password = $("#reg_password").val();
    var c_password = $("input[name='c_password']").val();

    // if (!username) {
    //     layer.msg("请输入帐号", {
    //         icon: 5,
    //         time: 1000 //2秒关闭（如果不配置，默认是3秒）
    //     });
    //     return false
    // }
    if (!password) {
        layer.msg("请输入密码", {
            icon: 5,
            time: 1000 //2秒关闭（如果不配置，默认是3秒）
        });
        return false
    }
    if (password != c_password) {
        layer.msg("密码和确认密码不一致", {
            icon: 5,
            time: 1000 //2秒关闭（如果不配置，默认是3秒）
        });
        return false
    }

    // var IsBy = $.idcode.validateCode()  //调用返回值，返回值结果为true或者false
    // if(IsBy){
    // }else {
    //     layer.msg("Incorrect CAPTCHA", {
    //         icon: 5,
    //         time: 1000 //2秒关闭（如果不配置，默认是3秒）
    //     });
    //     return false
    // }

    // var data = $("#regform").serialize();

    var data = new FormData($("#regForm")[0]);
    // data.append("supporting_documents", supporting_documents);

    $.ajax({
        type: 'post',
        url: "/index/Member/register",
        data: data,
        async: false,
        cache: false,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function (data) {
            if (data.code > 0) {
                layer.msg(data.info, {
                    icon: 6,
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    console.log(data)
                    if (data.data) {
                        //跳转页面
                        // window.location.href = data.data;
                        window.location.reload();
                    } else {
                        //跳转页面
                        // window.location.reload();
                        window.location.href = "/index/Member/index";
                    }

                });
            } else {
                layer.msg(data.info, {
                    icon: 5,
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    return false
                });
            }

        },
        error: function () {
            layer.msg("注册失败，请稍后再试！", {
                icon: 5,
                time: 2000 //2秒关闭（如果不配置，默认是3秒）
            }, function () {
                return false
            });
        }
    })
}).on('click', "#logOut", function () {
    /*用户登出事件*/
    var data = 0
    $.ajax({
        type: 'post',
        url: "/index/Member/logout",
        data: data,  //重点必须为一个变量如：data
        dataType: 'json',
        success: function (data) {
            if (data.code > 0) {
                layer.msg(data.info, {
                    icon: 6,
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    window.location.href = "/index";
                    // window.location.reload();
                });
            }

        },
    })


}).on('click', "#btnSendEmailCode", function () {

    /* ★☆★☆★☆★☆★☆★☆★☆ 发送邮箱验证码事件 ★☆★☆★☆★☆★☆★☆★☆ */

    var email = $("#login_email").val();
    $(this).attr("disabled", true);
    $(this).addClass("code-btn-disabled");
    var second = 60;
    $(this).text((second) + "秒后重发");
    var interval = setInterval(function () {
        second--;
        $("#btnSendEmailCode").text((second) + "秒后重发");
        if (second === -1) {
            $("#btnSendEmailCode").text("重发验证码");
            clearInterval(interval);
            $("#btnSendEmailCode").attr("disabled", false);
            $("#btnSendEmailCode").removeClass("code-btn-disabled");
        }
    }, 1000);

    $.ajax({
        type: 'post',
        url: "/index/Member/email_code",
        data: { email: email },  //重点必须为一个变量如：data
        dataType: 'json',
        success: function (data) {
            if (data.code > 0) {
                layer.msg(data.info, {
                    icon: 6,
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    // window.location.href = "/index";
                    // window.location.reload();
                });
            } else {
                layer.msg(data.info, {
                    icon: 5,
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    // window.location.href = "/index";
                    // window.location.reload();
                });
            }

        },
        error: function () {
            layer.msg("发送失败，请稍后再试！", {
                icon: 5,
                time: 2000 //2秒关闭（如果不配置，默认是3秒）
            }, function () {
                return false
            });
        }
    })
});

/* ★☆★☆★☆★☆★☆★☆★☆ 上传头像处理 START ★☆★☆★☆★☆★☆★☆★☆ */
$(".avatar").click(function () {
    $("#file").trigger("click")
})
//ios去掉capture属性
var file = document.querySelector('input');
if (getIos()) {
    file.removeAttribute("capture");
}
function getIos () {
    var ua = navigator.userAgent.toLowerCase();
    if (ua.match(/iPhone\sOS/i) == "iphone os") {
        return true;
    } else {
        return false;
    }
}
$('input[type=file]').on('change', function () {
    var reader = new FileReader();
    reader.onload = function (e) {
        console.log(reader.result);  //或者 e.target.result都是一样的，都是base64码
        $(".avatar").attr("src", reader.result);
        var b64 = reader.result;
        $.ajax({
            url: "/index/Member/uploadImage",
            type: "post",
            data: {
                photo: b64,
            },
            dataType: 'json',
            success: function (data) {
                if (data.code > 0) {
                    layer.msg(data.info, {
                        icon: 6,
                        time: 2000 //2秒关闭（如果不配置，默认是3秒）
                    }, function () {
                        setTimeout(function () {
                            return false;
                        }, 1000);

                    });
                } else {
                    layer.msg(data.info, {
                        icon: 5,
                        time: 2000 //2秒关闭（如果不配置，默认是3秒）
                    }, function () {
                        return false
                    });
                }
            },

        })
    }
    reader.readAsDataURL(this.files[0])
})

/* ★☆★☆★☆★☆★☆★☆★☆ 发送手机验证码事件 ★☆★☆★☆★☆★☆★☆★☆ */
$(document).on('click', '[data-btn="code"]', function () {
    $form = $(this).closest('form');
    var phone = $form.find('[data-val="phone"]').val();
    if (phone == '') {
        layer.msg('请填写手机号', { icon: 5 })
        return;
    }
    $(this).attr("disabled", true);
    $(this).addClass("code-btn-disabled");
    var second = 60;
    $(this).text((second) + "秒后重发");
    var interval = setInterval(function () {
        second--;
        $("#code").text((second) + "秒后重发");
        if (second === -1) {
            $("#code").text("重发验证码");
            clearInterval(interval);
            $("#code").attr("disabled", false);
            $("#code").removeClass("code-btn-disabled");
        }
    }, 1000);

    $.ajax({
        type: 'post',
        url: "/index/Member/sms",
        data: { phone: phone },  //重点必须为一个变量如：data
        dataType: 'json',
        success: function (data) {
            if (data.code > 0) {
                layer.msg(data.info, {
                    icon: 6,
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    // window.location.href = "/index";
                    // window.location.reload();
                });
            } else {
                layer.msg(data.info, {
                    icon: 5,
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    // window.location.href = "/index";
                    // window.location.reload();
                });
            }
        },
        error: function () {
            layer.msg("发送失败，请稍后再试！", {
                icon: 5,
                time: 2000 //2秒关闭（如果不配置，默认是3秒）
            }, function () {
                return false
            });
        }
    })
});

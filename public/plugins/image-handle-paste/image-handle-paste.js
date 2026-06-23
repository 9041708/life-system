(function() {
    var factory = function (exports) {
        var $ = jQuery;
        var pluginName = "image-handle-paste";

        exports.fn.imagePaste = function() {
            var _this   = this;
            var cm      = _this.cm;
            var settings = _this.settings;
            var id      = _this.id;

            if (!settings.imageUpload || !settings.imageUploadURL) {
                console.log('[imagePaste] 图片上传未开启或未配置上传地址');
                return false;
            }

            console.log('[imagePaste] 插件已激活，监听粘贴事件');

            // 用捕获模式监听整个 document 的 paste 事件
            document.addEventListener('paste', function (e) {
                // 判断焦点是否在当前编辑器内
                var active = document.activeElement;
                var editorEl = document.getElementById(id);
                if (!active || !editorEl || !editorEl.contains(active)) return;

                var cd = e.clipboardData || (e.originalEvent && e.originalEvent.clipboardData);
                if (!cd || !cd.items) return;

                for (var i = 0; i < cd.items.length; i++) {
                    if (cd.items[i].type && cd.items[i].type.indexOf('image') !== -1) {
                        e.preventDefault();
                        var file = cd.items[i].getAsFile();
                        if (!file) return;

                        console.log('[imagePaste] 检测到图片粘贴，准备上传', file);

                        var fieldName = settings.imageUploadField || 'editormd-image-file';
                        var formData = new FormData();
                        formData.append(fieldName, file, 'paste_' + Date.now() + '.png');

                        $.ajax({
                            url: settings.imageUploadURL,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (ret) {
                                ret = (typeof ret === 'string') ? JSON.parse(ret) : ret;
                                console.log('[imagePaste] 上传响应', ret);
                                if (ret.success === 1 && ret.url) {
                                    cm.replaceSelection('![](' + ret.url + ')');
                                    // 触发 onchange 以启用自动保存
                                    if (typeof settings.onchange === 'function') {
                                        settings.onchange();
                                    }
                                } else {
                                    alert('图片上传失败：' + (ret.message || '未知错误'));
                                }
                            },
                            error: function (xhr, status, err) {
                                console.log('[imagePaste] 上传请求失败', err);
                                alert('图片上传请求失败');
                            }
                        });
                        return;
                    }
                }
            }, true);
        };
    };

    // 注册插件
    if (typeof require === "function" && typeof exports === "object" && typeof module === "object") {
        module.exports = factory;
    } else if (typeof define === "function") {
        if (define.amd) {
            define(["editormd"], function(editormd) { factory(editormd); });
        } else {
            define(function(require) {
                var editormd = require("./../../editormd");
                factory(editormd);
            });
        }
    } else {
        factory(window.editormd);
    }
})();

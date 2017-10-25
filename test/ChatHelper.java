package com.mizhi.huanpeng.utils;

import android.text.TextUtils;

import com.google.gson.Gson;
import com.google.gson.JsonObject;
import com.google.gson.reflect.TypeToken;

import com.mizhi.huanpeng.constants.ErrorCode;
import com.mizhi.huanpeng.domain.ErrorBean;
import com.mizhi.huanpeng.rong.RxBus;
import com.sixrooms.chatting.SocketServer;

import org.json.JSONException;
import org.json.JSONObject;

import java.io.IOException;
import java.util.Map;

import io.reactivex.BackpressureStrategy;
import io.reactivex.Flowable;
import io.reactivex.FlowableOnSubscribe;
import io.reactivex.android.schedulers.AndroidSchedulers;
import io.reactivex.disposables.CompositeDisposable;

/**
 * Created by sh on 2016/3/18 13:45.
 */
public class ChatHelper implements SocketServer.Callback {
    public static final int SOCKET_TAG = 110;
    public static String address = "";
    public static String port = "";
    private SocketServer server;
    private OnReceiveMessageListener mListener;
    private CompositeDisposable mDisposables = new CompositeDisposable();

    public void login(final String uid, final String encpass, final String roomId) {
        //1.构造函数的参数通过访问php接口获得。
        if (TextUtils.isEmpty(address) || TextUtils.isEmpty(port)) {
            LogUtil.i("啊啊啊" + address + "    " + port);
            onErrorCallback(SocketServer.ERR_NET);
            return;
        }
        try {
            if (server == null) {
                server = new SocketServer();
            } else {
                server.disconnect();
                server = new SocketServer();
            }
            //2.登录成功、收到消息、错误代码的回调注册。
            server.addCallback(this);
            //3.登录
            server.login(address, port, uid, encpass, roomId);
        } catch (Exception e) {
            e.printStackTrace();
            onErrorCallback(SocketServer.ERR_NET);
        }
    }

    public void sendMessage(final String message, String identity) {
        if (server != null) {
            new Thread(() -> {
                try {
                    if (server != null)
                        server.sendPacketMessage(ChatUtil.fomatNormalMessage(message, identity));
                } catch (Exception e) {
                    e.printStackTrace();
//                mListener.onError(new ErrorBean(ErrorCode.ERROR_CODE_SOCKET_SEND, ErrorCode.ERROR_DESC_SOCKET_SEND + "\n" + e.toString()));
                }
            }).start();
        } else {
            mListener.onError(new ErrorBean(ErrorCode.ERROR_CODE_SOCKET, ErrorCode.ERROR_DESC_SOCKET + "\n" + "未登录,请先登录"));
        }
    }

    public void sendPresent(final String encpass, final int gid, final String liveid, String identity) {
        if (server != null) {
            new Thread(() -> {
                try {
                    if (server != null)
                        server.sendPacketMessage(ChatUtil.formatPresentMessage(encpass, gid, liveid, identity));
                } catch (Exception e) {
                    e.printStackTrace();
//                mListener.onError(new ErrorBean(ErrorCode.ERROR_CODE_SOCKET_SEND, ErrorCode.ERROR_DESC_SOCKET_SEND + "\n" + e.toString()));
                }
            }).start();
        } else {
            mListener.onError(new ErrorBean(ErrorCode.ERROR_CODE_SOCKET, ErrorCode.ERROR_DESC_SOCKET + "\n" + "未登录,请先登录"));
        }
    }

    public void sendBackPresent(final String encpass, final int gid, final String liveid, String identity) {
        if (server != null) {
            new Thread(() -> {
                try {
                    if (server != null)
                        server.sendPacketMessage(ChatUtil.formatBackPresentMessage(encpass, gid, liveid, identity));
                } catch (Exception e) {
                    e.printStackTrace();
//                mListener.onError(new ErrorBean(ErrorCode.ERROR_CODE_SOCKET_SEND, ErrorCode.ERROR_DESC_SOCKET_SEND + "\n" + e.toString()));
                }
            }).start();
        } else {
            mListener.onError(new ErrorBean(ErrorCode.ERROR_CODE_SOCKET, ErrorCode.ERROR_DESC_SOCKET + "\n" + "未登录,请先登录"));
        }
    }

    public void sendPresent(final String encpass, final int gid, final String liveid, final int num, String identity) {
        if (server != null) {
            new Thread(() -> {
                try {
                    server.sendPacketMessage(ChatUtil.formatPresentMessage(encpass, gid, liveid, num, identity));
                } catch (Exception e) {
                    e.printStackTrace();
//                mListener.onError(new ErrorBean(ErrorCode.ERROR_CODE_SOCKET_SEND, ErrorCode.ERROR_DESC_SOCKET_SEND + "\n" + e.toString()));
                }
            }).start();
        } else {
            mListener.onError(new ErrorBean(ErrorCode.ERROR_CODE_SOCKET, ErrorCode.ERROR_DESC_SOCKET + "\n" + "未登录,请先登录"));
        }
    }

    public void sendShareMessage(String mid) {
        if (server != null) {
            new Thread(() -> {
                try {
                    server.sendPacketMessage(ChatUtil.formatShareMessage(mid));
                } catch (Exception e) {
                    e.printStackTrace();
                }
            }).start();
        } else {
            mListener.onError(new ErrorBean(ErrorCode.ERROR_CODE_SOCKET, ErrorCode.ERROR_DESC_SOCKET + "\n" + "未登录,请先登录"));
        }
    }

    private void sendEnterRoom() {
        if (server != null) {
            new Thread(() -> {
                try {
                    server.sendPacketMessage(ChatUtil.formatEnterRoomMessage());
                } catch (Exception e) {
                    e.printStackTrace();
                }
            }).start();
        } else {
            mListener.onError(new ErrorBean(ErrorCode.ERROR_CODE_SOCKET, ErrorCode.ERROR_DESC_SOCKET + "\n" + "未登录,请先登录"));
        }
    }

    public void unLogin() {
        if (server != null) {
            server.disconnect();
        }
        mListener = null;
        mDisposables.dispose();
    }

    @Override
    public void onMessageCallback(SocketServer.ResponseMessage responseMessage) {
        if (responseMessage.getCommand() == SocketServer.ResponseMessage.COMMAND_TYPE_RECIEVEMESSAGE) {
            Gson gson = new Gson();
            LogUtil.i("啊啊啊" + responseMessage.getContent());
            //按照你们定义的数据格式读信息内容
            String json = responseMessage.getContent();
            try {
                JSONObject object = new JSONObject(responseMessage.getContent());
                object.remove("custom");
                json = object.toString();
            } catch (JSONException e) {
                e.printStackTrace();
            }

            try {
                JSONObject object = new JSONObject(responseMessage.getContent());
                String packBack = object.get("packBack").toString();
                object.remove("packBack");
                json = object.toString();
                PackageInfo packageInfo = new Gson().fromJson(packBack, PackageInfo.class);
                RxBus.getDefault().post(packageInfo);
            } catch (Exception e) {
            }
            Map<String, String> messageMap = gson.fromJson(
                    json,
                    new TypeToken<Map<String, String>>() {
                    }.getType());
            if (mListener != null) {
                mDisposables.add(Flowable.create((FlowableOnSubscribe<Boolean>) e -> e.onNext(true), BackpressureStrategy.BUFFER).observeOn(AndroidSchedulers.mainThread()).subscribe(aBoolean -> {
                    mListener.onReceiveMessage(messageMap);
                }));
            }

        }
    }

    public class PackageInfo {
        private String surplus = "";
        private String giftID = "";

        public PackageInfo(String surplus, String giftID) {
            this.surplus = surplus;
            this.giftID = giftID;
        }

        @Override
        public String toString() {
            return "PackageInfo{" +
                    "surplus='" + surplus + '\'' +
                    ", giftID='" + giftID + '\'' +
                    '}';
        }

        public String getSurplus() {
            return surplus;
        }

        public String getGiftID() {
            return giftID;
        }

        public void setSurplus(String surplus) {
            this.surplus = surplus;
        }

        public void setGiftID(String giftID) {
            this.giftID = giftID;
        }
    }

    public void onErrorCallback(int error) {
        if (mListener == null) {
            return;
        }
        switch (error) {
            case SocketServer.ERR_ADDR:
                mDisposables.add(Flowable.create((FlowableOnSubscribe<Boolean>) e -> e.onNext(true), BackpressureStrategy.BUFFER).observeOn(AndroidSchedulers.mainThread()).subscribe(aBoolean -> mListener.onError(new ErrorBean(ErrorCode.ERROR_CODE_SOCKET, ErrorCode.ERROR_DESC_SOCKET + "\n" + "地址错误"))));
                break;
            case SocketServer.ERR_NET:
                mDisposables.add(Flowable.create((FlowableOnSubscribe<Boolean>) e -> e.onNext(true), BackpressureStrategy.BUFFER).observeOn(AndroidSchedulers.mainThread()).subscribe(aBoolean -> mListener.onError(new ErrorBean(ErrorCode.ERROR_CODE_SOCKET, ErrorCode.ERROR_DESC_SOCKET + "\n" + "网络错误"))));
                break;
            case SocketServer.ERR_TIMEOUT:
                mDisposables.add(Flowable.create((FlowableOnSubscribe<Boolean>) e -> e.onNext(true), BackpressureStrategy.BUFFER).observeOn(AndroidSchedulers.mainThread()).subscribe(aBoolean -> mListener.onError(new ErrorBean(ErrorCode.ERROR_CODE_SOCKET, ErrorCode.ERROR_DESC_SOCKET + "\n" + "连接超时"))));
                break;
            default:
                break;
        }
    }

    public void setOnReceiveMessageListener(OnReceiveMessageListener listener) {
        this.mListener = listener;
    }

    public interface OnReceiveMessageListener {
        void onReceiveMessage(Map<String, String> params);

        void onLoginSuccess();

        void onError(ErrorBean errorBean);
    }

    @Override
    public void onLoginSucceed() {
        LogUtil.i("啊啊啊" + "success");
        //收到这个回调以后才可以发送聊天信息
        sendEnterRoom();
        if (mListener != null)
            mDisposables.add(Flowable.create((FlowableOnSubscribe<Boolean>) e -> e.onNext(true), BackpressureStrategy.BUFFER).observeOn(AndroidSchedulers.mainThread()).subscribe(aBoolean -> mListener.onLoginSuccess()));
    }

}

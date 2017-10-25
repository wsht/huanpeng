//
// Source code recreated from a .class file by IntelliJ IDEA
// (powered by Fernflower decompiler)
//

package com.sixrooms.chatting;

import android.util.Base64;
import android.util.Log;
import com.sixrooms.chatting.ZLibUtils;
import java.io.BufferedReader;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.io.UnsupportedEncodingException;
import java.net.InetSocketAddress;
import java.net.Socket;
import java.net.UnknownHostException;
import java.nio.CharBuffer;
import java.util.Timer;
import java.util.TimerTask;
import java.util.zip.GZIPInputStream;
import java.util.zip.GZIPOutputStream;

public class SocketServer {
    private static final int STATE_STOP = 0;
    private static final int STATE_CONNECTING = 1;
    private static final int STATE_START = 2;
    private int mState = 0;
    private String mIpAddress;
    private String mPort;
    private String mUid;
    private String mEncpass;
    private String mRoomId;
    private Socket socket;
    private OutputStream outputStream = null;
    private ByteArrayOutputStream contentOutputStream = null;
    private GZIPInputStream gzipInputStream;
    private GZIPOutputStream gzipOutputStream;
    BufferedReader bufferedReader;
    private int mTimeOutCount = 0;
    private boolean stopFlag = false;
    private final String CMD_TAG = "command=";
    private final String UID_TAG = "uid=";
    private final String ENC_TAG = "encpass=";
    private final String RID_TAG = "roomid=";
    private final String CONTENT_TAG = "content=";
    private final String RETURN = "\r\n";
    private final int LEN_MAX_SIZE = 8;
    public static final int ERR_NET = 200;
    public static final int ERR_ADDR = 201;
    public static final int ERR_TIMEOUT = 202;
    private SocketServer.Callback mCallback = null;
    private Runnable socketRunnable = new Runnable() {
        public void run() {
            SocketServer.this.contentOutputStream = new ByteArrayOutputStream();

            try {
                SocketServer.this.gzipOutputStream = new GZIPOutputStream(SocketServer.this.contentOutputStream);
            } catch (IOException var8) {
                var8.printStackTrace();
            }

            try {
                SocketServer.this.connect();
            } catch (NumberFormatException var5) {
                SocketServer.this.onError(201);
                var5.printStackTrace();
                return;
            } catch (UnknownHostException var6) {
                SocketServer.this.onError(201);
                var6.printStackTrace();
                return;
            } catch (IOException var7) {
                SocketServer.this.onError(200);
                var7.printStackTrace();
                return;
            }

            SocketServer.this.mState = 2;

            try {
                SocketServer.this.sendPacketLogin(SocketServer.this.mUid, SocketServer.this.mEncpass, SocketServer.this.mRoomId);
            } catch (IOException var4) {
                SocketServer.this.onError(200);
                var4.printStackTrace();
                return;
            }

            try {
                SocketServer.this.readPacket();
            } catch (IOException var3) {
                SocketServer.this.onError(200);
                var3.printStackTrace();
                return;
            }

            try {
                SocketServer.this.socket.close();
            } catch (IOException var2) {
                SocketServer.this.onError(200);
                var2.printStackTrace();
            }

            SocketServer.this.socket = null;
        }
    };
    private Thread socketThread = null;
    private Timer pingTimer = null;
    private TimerTask pingTimerTask = new TimerTask() {
        public void run() {
            if(SocketServer.this.mTimeOutCount > 1) {
                SocketServer.this.onError(202);
            } else {
                SocketServer.this.mTimeOutCount = SocketServer.this.mTimeOutCount + 1;
                String contentString = Base64.encodeToString(ZLibUtils.compress("noop".getBytes()), 2).replace("+", "(").replace("/", ")").replace("=", "@");
                StringBuilder stringBuilder = new StringBuilder();
                stringBuilder.append("command=").append("sendmessage").append("\r\n").append("content=").append(contentString).append("\r\n");
                int packetLength = stringBuilder.toString().length();
                String lengthString = String.valueOf(packetLength);
                int appendZeroSize = 8 - lengthString.length();
                StringBuilder lengthStringBuilder = new StringBuilder();

                for(int e = 0; e < appendZeroSize; ++e) {
                    lengthStringBuilder.append("0");
                }

                lengthStringBuilder.append(lengthString);
                lengthStringBuilder.append("\r\n");
                stringBuilder.insert(0, lengthStringBuilder.toString());

                try {
                    if(SocketServer.this.outputStream != null) {
                        SocketServer.this.outputStream.write(stringBuilder.toString().getBytes());
                    }
                } catch (IOException var8) {
                    SocketServer.this.onError(200);
                    var8.printStackTrace();
                }

            }
        }
    };
    private CharBuffer charBuffer = null;

    public SocketServer() {
        Log.d("Huanpeng", "APP call new SocketServer()");
    }

    public void addCallback(SocketServer.Callback callback) {
        this.mCallback = callback;
    }

    private void onError(int errno) {
        this.mState = 0;
        this.pingTimer.cancel();
        if(!this.stopFlag && this.mCallback != null) {
            this.mCallback.onErrorCallback(errno);
        }

    }

    public void login(String ipAddress, String port, String uid, String encpass, String roomid) {
        Log.d("Huanpeng", "APP call login");
        this.mIpAddress = ipAddress;
        this.mPort = port;
        this.mUid = uid;
        this.mEncpass = encpass;
        this.mRoomId = roomid;
        this.pingTimer = new Timer();
        this.mState = 1;
        this.socket = new Socket();
        this.socketThread = new Thread(this.socketRunnable, "socketThread");
        this.socketThread.start();
    }

    private void connect() throws NumberFormatException, UnknownHostException, IOException {
        this.stopFlag = false;
        this.charBuffer = CharBuffer.allocate(1000000);
        this.socket.connect(new InetSocketAddress(this.mIpAddress, Integer.parseInt(this.mPort)), 2000);
        this.socket.setSoTimeout(20000);
        this.outputStream = this.socket.getOutputStream();
        this.bufferedReader = new BufferedReader(new InputStreamReader(this.socket.getInputStream()));
    }

    public void disconnect() {
        this.stopFlag = true;
        Log.d("Huanpeng", "APP call disconnect");
        if(this.socket != null) {
            try {
                this.socket.close();
            } catch (IOException var3) {
                var3.printStackTrace();
            }

            try {
                this.socketThread.join();
            } catch (InterruptedException var2) {
                var2.printStackTrace();
            }

            Log.d("Huanpeng", "APP disconnect return");
        }
    }

    private void readPacket() throws IOException {
        this.pingTimer.schedule(this.pingTimerTask, 10000L, 10000L);

        while(true) {
            String packetLength;
            do {
                if((packetLength = this.bufferedReader.readLine()) == null) {
                    this.pingTimer.cancel();
                    this.pingTimerTask.cancel();
                    return;
                }
            } while(this.mState == 0);

            int leftSize = Integer.parseInt(packetLength);
            this.charBuffer.position(0);
            this.charBuffer.limit(leftSize);

            while(leftSize > 0) {
                int responseMessage = this.bufferedReader.read(this.charBuffer);
                leftSize -= responseMessage;
            }

            this.mTimeOutCount = 0;
            this.charBuffer.position(0);
            SocketServer.ResponseMessage responseMessage1 = this.decodePacket(this.charBuffer.toString());
            if(this.mCallback != null) {
                if(responseMessage1.getContent().equals("login.success")) {
                    Log.d("Huanpeng", "APP onLoginSucceed");
                    this.mCallback.onLoginSucceed();
                } else {
                    this.mCallback.onMessageCallback(responseMessage1);
                }
            }

            try {
                Thread.sleep(1L);
            } catch (InterruptedException var5) {
                var5.printStackTrace();
            }
        }
    }

    private SocketServer.ResponseMessage decodePacket(String packetString) {
        SocketServer.ResponseMessage message = new SocketServer.ResponseMessage();
        String[] messageType = packetString.split("\r\n");
        if(messageType.length != 3) {
            System.out.println("decode err messageType.length != 3");
            return null;
        } else {
            if(messageType[0].equals("enc=no")) {
                message.setEnc(false);
                message.setContent(messageType[2].replace("content=", ""));
            } else {
                message.setEnc(true);
                message.setContent(new String(ZLibUtils.decompress(Base64.decode(messageType[2].replace("content=", "").replace("(", "+").replace(")", "/").replace("@", "="), 2))));
            }

            if(messageType[1].equals("command=receivemessage")) {
                message.setCommand(3);
            }

            if(messageType[1].equals("command=result")) {
                message.setCommand(1);
            }

            return message;
        }
    }

    private boolean sendPacketLogin(String uid, String encpass, String roomid) throws IOException {
        StringBuilder stringBuilder = new StringBuilder();
        stringBuilder.append("command=").append("login").append("\r\n").append("uid=").append(uid).append("\r\n").append("encpass=").append(encpass).append("\r\n").append("roomid=").append(roomid).append("\r\n");
        int packetLength = stringBuilder.toString().length();
        String lengthString = String.valueOf(packetLength);
        int appendZeroSize = 8 - lengthString.length();
        StringBuilder lengthStringBuilder = new StringBuilder();

        for(int i = 0; i < appendZeroSize; ++i) {
            lengthStringBuilder.append("0");
        }

        lengthStringBuilder.append(lengthString);
        lengthStringBuilder.append("\r\n");
        stringBuilder.insert(0, lengthStringBuilder.toString());
        this.outputStream.write(stringBuilder.toString().getBytes());
        return true;
    }

    public boolean sendPacketMessage(String message) throws UnsupportedEncodingException, IOException {
        Log.d("send", message);
        String contentString = Base64.encodeToString(ZLibUtils.compress(message.getBytes()), 2).replace("+", "(").replace("/", ")").replace("=", "@");
        StringBuilder stringBuilder = new StringBuilder();
        stringBuilder.append("command=").append("sendmessage").append("\r\n").append("content=").append(contentString).append("\r\n");
        int packetLength = stringBuilder.toString().length();
        String lengthString = String.valueOf(packetLength);
        int appendZeroSize = 8 - lengthString.length();
        StringBuilder lengthStringBuilder = new StringBuilder();

        for(int i = 0; i < appendZeroSize; ++i) {
            lengthStringBuilder.append("0");
        }

        lengthStringBuilder.append(lengthString);
        lengthStringBuilder.append("\r\n");
        stringBuilder.insert(0, lengthStringBuilder.toString());
        this.outputStream.write(stringBuilder.toString().getBytes("UTF-8"));
        return true;
    }

    public interface Callback {
        void onMessageCallback(SocketServer.ResponseMessage var1);

        void onErrorCallback(int var1);

        void onLoginSucceed();
    }

    public class ResponseMessage {
        public static final int COMMAND_TYPE_LOGIN = 0;
        public static final int COMMAND_TYPE_RESULT = 1;
        public static final int COMMAND_TYPE_SENDMESSAGE = 2;
        public static final int COMMAND_TYPE_RECIEVEMESSAGE = 3;
        public static final int COMMAND_TYPE_DISCONNECT = 4;
        boolean isEnc;
        int command;
        String content;

        public ResponseMessage() {
        }

        public boolean isEnc() {
            return this.isEnc;
        }

        public void setEnc(boolean isEnc) {
            this.isEnc = isEnc;
        }

        public int getCommand() {
            return this.command;
        }

        public void setCommand(int command) {
            this.command = command;
        }

        public String getContent() {
            return this.content;
        }

        public void setContent(String content) {
            this.content = content;
        }
    }

    public class SocketMessage {
        private int t;
        private String msg;
        private String mid;

        public SocketMessage() {
        }

        public int getT() {
            return this.t;
        }

        public void setT(int t) {
            this.t = t;
        }

        public String getMsg() {
            return this.msg;
        }

        public void setMsg(String msg) {
            this.msg = msg;
        }

        public String getMid() {
            return this.mid;
        }

        public void setMid(String mid) {
            this.mid = mid;
        }
    }
}

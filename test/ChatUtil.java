package com.mizhi.huanpeng.utils;

/**
 * Created by sh on 2016/3/18 9:52.
 */
public class ChatUtil {
    public static String fomatNormalMessage(String message, String identity) {
        String mid = String.valueOf(System.currentTimeMillis());
        return "{\"t\":\"100\",\"msg\":\"" + message + "\",\"mid\":\"" + mid + "\",\"way\":\"1\",\"identity\":\"" + identity + "\"}";
    }

    public static String formatPresentMessage(String encpass, int gid, String liveid, String identity) {
        return "{\"t\":\"103\",\"enc\":\"" + encpass + "\",\"gid\":\"" + String.valueOf(gid) + "\",\"liveid\":\"" + liveid + "\",\"way\":\"1\",\"identity\":\"" + identity + "\"}";
    }

    public static String formatBackPresentMessage(String encpass, int gid, String liveid, String identity) {
        return "{\"t\":\"103\",\"enc\":\"" + encpass + "\",\"gid\":\"" + String.valueOf(gid) + "\",\"liveid\":\"" + liveid + "\",\"way\":\"1\",\"identity\":\"" + identity + "\",\"sendType\":\"1\"}";
    }

    public static String formatPresentMessage(String encpass, int gid, String liveid, int num, String identity) {
        return "{\"t\":\"102\",\"enc\":\"" + encpass + "\",\"gid\":\"" + String.valueOf(gid) + "\",\"liveid\":\"" + liveid + "\",\"num\":\"" + String.valueOf(num) + "\",\"way\":\"1\",\"identity\":\"" + identity + "\"}";
    }

    public static String formatEnterRoomMessage() {
        return "{\"t\":\"104\",\"mid\":\"" + System.currentTimeMillis() + "\"}";
    }

    public static String formatShareMessage(String mid) {
        return "{\"t\":\"105\",\"mid\":\"" + mid + "\"}";
    }
}

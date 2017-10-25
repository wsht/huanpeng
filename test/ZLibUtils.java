//
// Source code recreated from a .class file by IntelliJ IDEA
// (powered by Fernflower decompiler)
//

package com.sixrooms.chatting;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.util.zip.Deflater;
import java.util.zip.DeflaterOutputStream;
import java.util.zip.Inflater;
import java.util.zip.InflaterInputStream;

public abstract class ZLibUtils {
    public ZLibUtils() {
    }

    public static byte[] compress(byte[] data) {
        byte[] output = new byte[0];
        Deflater compresser = new Deflater(1, true);
        compresser.reset();
        compresser.setInput(data);
        compresser.finish();
        ByteArrayOutputStream bos = new ByteArrayOutputStream(data.length);

        try {
            byte[] e = new byte[1024];

            while(!compresser.finished()) {
                int i = compresser.deflate(e);
                bos.write(e, 0, i);
            }

            output = bos.toByteArray();
        } catch (Exception var14) {
            output = data;
            var14.printStackTrace();
        } finally {
            try {
                bos.close();
            } catch (IOException var13) {
                var13.printStackTrace();
            }

        }

        compresser.end();
        return output;
    }

    public static void compress(byte[] data, OutputStream os) {
        DeflaterOutputStream dos = new DeflaterOutputStream(os);

        try {
            dos.write(data, 0, data.length);
            dos.finish();
            dos.flush();
        } catch (IOException var4) {
            var4.printStackTrace();
        }

    }

    public static byte[] decompress(byte[] data) {
        byte[] output = new byte[0];
        Inflater decompresser = new Inflater(true);
        decompresser.reset();
        decompresser.setInput(data);
        ByteArrayOutputStream o = new ByteArrayOutputStream(data.length);

        try {
            byte[] e = new byte[1024];

            while(!decompresser.finished()) {
                int i = decompresser.inflate(e);
                o.write(e, 0, i);
            }

            output = o.toByteArray();
        } catch (Exception var14) {
            output = data;
            var14.printStackTrace();
        } finally {
            try {
                o.close();
            } catch (IOException var13) {
                var13.printStackTrace();
            }

        }

        decompresser.end();
        return output;
    }

    public static byte[] decompress(InputStream is) {
        InflaterInputStream iis = new InflaterInputStream(is);
        ByteArrayOutputStream o = new ByteArrayOutputStream(1024);

        try {
            int e = 1024;
            byte[] buf = new byte[e];

            while((e = iis.read(buf, 0, e)) > 0) {
                o.write(buf, 0, e);
            }
        } catch (IOException var5) {
            var5.printStackTrace();
        }

        return o.toByteArray();
    }
}

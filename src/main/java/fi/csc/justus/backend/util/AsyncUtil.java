package fi.csc.justus.backend.util;

import java.util.concurrent.CompletableFuture;
import java.util.function.Supplier;

import static java.util.concurrent.CompletableFuture.supplyAsync;

public class AsyncUtil {
    public static <T> CompletableFuture<T> async(Supplier<T> suplr) {
        return supplyAsync(suplr);
    }
}

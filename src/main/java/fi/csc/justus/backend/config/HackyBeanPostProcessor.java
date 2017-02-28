package fi.csc.justus.backend.config;

import fi.csc.justus.backend.spring.handler.CompletionStageReturnValueHandler;
import org.springframework.beans.BeansException;
import org.springframework.beans.factory.config.BeanPostProcessor;
import org.springframework.stereotype.Component;
import org.springframework.web.method.support.HandlerMethodReturnValueHandler;
import org.springframework.web.servlet.mvc.method.annotation.DeferredResultMethodReturnValueHandler;
import org.springframework.web.servlet.mvc.method.annotation.RequestMappingHandlerAdapter;

import java.util.ArrayList;
import java.util.List;

/**
 * Adds CompletionStageReturnValueHandler after the RequestMappingHandlerAdapter bean is initialized.
 * This was in the MvcConfig's @PostConstruct before but it led to the failure of Swagger and maybe other stuff.
 */
@Component
public class HackyBeanPostProcessor implements BeanPostProcessor {

    @Override
    public Object postProcessAfterInitialization(Object bean, String beanName) throws BeansException {
        if (bean instanceof RequestMappingHandlerAdapter) {
            RequestMappingHandlerAdapter adapter = (RequestMappingHandlerAdapter) bean;
            final List<HandlerMethodReturnValueHandler> originalHandlers = new ArrayList<>(
                adapter.getReturnValueHandlers());
            final int deferredPos = obtainValueHandlerPosition(originalHandlers, DeferredResultMethodReturnValueHandler.class);
            // Add our handler directly after the deferred handler.
            originalHandlers.add(deferredPos + 1, new CompletionStageReturnValueHandler());
            adapter.setReturnValueHandlers(originalHandlers);
        }
        return bean;
    }

    private int obtainValueHandlerPosition(final List<HandlerMethodReturnValueHandler> originalHandlers, Class<?> handlerClass) {
        for (int i = 0; i < originalHandlers.size(); i++) {
            final HandlerMethodReturnValueHandler valueHandler = originalHandlers.get(i);
            if (handlerClass.isAssignableFrom(valueHandler.getClass())) {
                return i;
            }
        }
        return -1;
    }

    @Override
    public Object postProcessBeforeInitialization(Object bean, String beanName) throws BeansException {
        return bean;
    }

}

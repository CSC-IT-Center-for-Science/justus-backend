package fi.csc.justus.backend.web.controller;

import fi.csc.justus.backend.jooq.tables.pojos.Avainsana;
import fi.csc.justus.backend.service.ApiService;

import io.swagger.annotations.ApiOperation;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.http.HttpEntity;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.List;
import javax.servlet.ServletContext;
import java.util.concurrent.CompletableFuture;

import static fi.csc.justus.backend.util.AsyncUtil.async;
import static fi.csc.justus.backend.util.ControllerUtil.badRequest;
import static fi.csc.justus.backend.util.ControllerUtil.ok;
import static fi.csc.justus.backend.util.ControllerUtil.newOrBust;
import static fi.csc.justus.backend.util.ControllerUtil.noContent;
import static fi.csc.justus.backend.util.ControllerUtil.getOr404;
import static org.springframework.web.bind.annotation.RequestMethod.GET;
import static org.springframework.web.bind.annotation.RequestMethod.POST;
import static org.springframework.web.bind.annotation.RequestMethod.PUT;
import static org.springframework.web.bind.annotation.RequestMethod.DELETE;

@RestController
@RequestMapping(value = "${api.url.prefix}" + AvainsanaController.path, produces = {
    MediaType.APPLICATION_JSON_VALUE
})
public class AvainsanaController {
    public static final String path = "/avainsana";

    @Autowired
    ApiService service;

    @Autowired
    ServletContext context;

    @RequestMapping(method = GET)
    public CompletableFuture<List<Avainsana>> getAll() {
        return async(() -> {
            return service.getAllAvainsana();
        });
    }

    @ApiOperation(value = "Palauttaa yksittäisen avainsanan", response = Avainsana.class)
    @RequestMapping(value = "/{id}", method = GET)
    public CompletableFuture<HttpEntity<Avainsana>> get(@PathVariable final Long id) {
        return getOr404(async(() -> service.getAvainsana(id)));
    }
    
    @ApiOperation(value = "Tallentaa tai päivittää avainsanan")
    @RequestMapping(method = POST)
    public ResponseEntity<?> post(@RequestBody Avainsana avainsana) {
        return newOrBust(service.createOrUpdateAvainsana(avainsana), context.getContextPath() + path);
    }

    @ApiOperation(value = "Päivittää avainsanan")
    @RequestMapping(value = "/{id}", method = PUT)
    public ResponseEntity<?> update(@PathVariable final Long id, @RequestBody Avainsana avainsana) {
        avainsana.setId(id);
        service.createOrUpdateAvainsana(avainsana);
        return noContent();
    }

    @ApiOperation(value = "Poistaa avainsanan")
    @RequestMapping(value = "/{id}", method = DELETE)
    public ResponseEntity<?> delete(@PathVariable final Long id) {
        service.removeAvainsana(id);
        return noContent();
    }
    
}

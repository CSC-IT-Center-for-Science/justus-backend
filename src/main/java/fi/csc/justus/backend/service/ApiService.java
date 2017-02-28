package fi.csc.justus.backend.service;

import fi.csc.justus.backend.jooq.tables.pojos.*;
import fi.csc.justus.backend.jooq.tables.records.*;

//import fi.csc.justus.backend.security.SecurityUtil;
import org.jooq.DSLContext;
import org.springframework.beans.factory.annotation.Autowired;
//import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.stereotype.Service;

//import java.sql.Timestamp;
//import java.time.Instant;
import java.util.List;
import java.util.Optional;
//import java.util.Collection;

import static fi.csc.justus.backend.jooq.Tables.*;

@Service
public class ApiService {

    @Autowired
    DSLContext dsl;

    //
    // Julkaisu
    //
    public List<Julkaisu> getAllJulkaisu() {
        return dsl.select(JULKAISU.fields()).from(JULKAISU).fetchInto(Julkaisu.class);
    }
    
    public Optional<Julkaisu> getJulkaisu(long id) {
        return Optional.ofNullable(
            dsl.select(JULKAISU.fields())
            .from(JULKAISU)
            .where(JULKAISU.ID.eq(id))
            .fetchOneInto(Julkaisu.class)
        );
    }
    
    //@PreAuthorize("isSignedIn()")
    public Optional<Long> createOrUpdateJulkaisu(Julkaisu julkaisu) {
        JulkaisuRecord lr = dsl.newRecord(JULKAISU, julkaisu);
        if (julkaisu.getId() != null) {
            dsl.executeUpdate(lr);
        } else {
            lr.store();
        }
        return Optional.ofNullable(lr.getId());
    }

    /*
    private void saveJulkaisu(Julkaisu julkaisu) {
        final JulkaisuRecord JulkaisuRecord = dsl.newRecord(JULKAISU, julkaisu);
        dsl.executeUpdate(JulkaisuRecord);
    }
    */
    
    //@PreAuthorize("isSignedIn()")
    public void removeJulkaisu(Long id) {
        dsl.delete(JULKAISU).where(JULKAISU.ID.eq(id)).execute();
    }

    //
    // Avainsana
    //
    public List<Avainsana> getAllAvainsana() {
        return dsl.select(AVAINSANA.fields()).from(AVAINSANA).fetchInto(Avainsana.class);
    }
    
    public Optional<Avainsana> getAvainsana(long id) {
        return Optional.ofNullable(
            dsl.select(AVAINSANA.fields())
            .from(AVAINSANA)
            .where(AVAINSANA.ID.eq(id))
            .fetchOneInto(Avainsana.class)
        );
    }
    
    //@PreAuthorize("isSignedIn()")
    public Optional<Long> createOrUpdateAvainsana(Avainsana avainsana) {
        AvainsanaRecord lr = dsl.newRecord(AVAINSANA, avainsana);
        if (avainsana.getId() != null) {
            dsl.executeUpdate(lr);
        } else {
            lr.store();
        }
        return Optional.ofNullable(lr.getId());
    }

    /*
    private void saveAvainsana(Avainsana avainsana) {
        final AvainsanaRecord AvainsanaRecord = dsl.newRecord(AVAINSANA, avainsana);
        dsl.executeUpdate(AvainsanaRecord);
    }
    */
    
    //@PreAuthorize("isSignedIn()")
    public void removeAvainsana(Long id) {
        dsl.delete(AVAINSANA).where(AVAINSANA.ID.eq(id)).execute();
    }
    
}

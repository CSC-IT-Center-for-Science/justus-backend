/**
 * This class is generated by jOOQ
 */
package fi.csc.justus.backend.jooq.tables;


import fi.csc.justus.backend.jooq.Appback;
import fi.csc.justus.backend.jooq.Keys;
import fi.csc.justus.backend.jooq.tables.records.TieteenalaRecord;

import java.util.Arrays;
import java.util.List;

import javax.annotation.Generated;

import org.jooq.Field;
import org.jooq.ForeignKey;
import org.jooq.Identity;
import org.jooq.Table;
import org.jooq.TableField;
import org.jooq.UniqueKey;
import org.jooq.impl.TableImpl;


/**
 * This class is generated by jOOQ.
 */
@Generated(
	value = {
		"http://www.jooq.org",
		"jOOQ version:3.7.0"
	},
	comments = "This class is generated by jOOQ"
)
@SuppressWarnings({ "all", "unchecked", "rawtypes" })
public class Tieteenala extends TableImpl<TieteenalaRecord> {

	private static final long serialVersionUID = -988714375;

	/**
	 * The reference instance of <code>appback.tieteenala</code>
	 */
	public static final Tieteenala TIETEENALA = new Tieteenala();

	/**
	 * The class holding records for this type
	 */
	@Override
	public Class<TieteenalaRecord> getRecordType() {
		return TieteenalaRecord.class;
	}

	/**
	 * The column <code>appback.tieteenala.id</code>.
	 */
	public final TableField<TieteenalaRecord, Long> ID = createField("id", org.jooq.impl.SQLDataType.BIGINT.nullable(false).defaulted(true), this, "");

	/**
	 * The column <code>appback.tieteenala.julkaisuid</code>.
	 */
	public final TableField<TieteenalaRecord, Long> JULKAISUID = createField("julkaisuid", org.jooq.impl.SQLDataType.BIGINT.nullable(false), this, "");

	/**
	 * The column <code>appback.tieteenala.tieteenalakoodi</code>.
	 */
	public final TableField<TieteenalaRecord, String> TIETEENALAKOODI = createField("tieteenalakoodi", org.jooq.impl.SQLDataType.CLOB.nullable(false), this, "");

	/**
	 * The column <code>appback.tieteenala.jnro</code>.
	 */
	public final TableField<TieteenalaRecord, Integer> JNRO = createField("jnro", org.jooq.impl.SQLDataType.INTEGER, this, "");

	/**
	 * Create a <code>appback.tieteenala</code> table reference
	 */
	public Tieteenala() {
		this("tieteenala", null);
	}

	/**
	 * Create an aliased <code>appback.tieteenala</code> table reference
	 */
	public Tieteenala(String alias) {
		this(alias, TIETEENALA);
	}

	private Tieteenala(String alias, Table<TieteenalaRecord> aliased) {
		this(alias, aliased, null);
	}

	private Tieteenala(String alias, Table<TieteenalaRecord> aliased, Field<?>[] parameters) {
		super(alias, Appback.APPBACK, aliased, parameters, "");
	}

	/**
	 * {@inheritDoc}
	 */
	@Override
	public Identity<TieteenalaRecord, Long> getIdentity() {
		return Keys.IDENTITY_TIETEENALA;
	}

	/**
	 * {@inheritDoc}
	 */
	@Override
	public UniqueKey<TieteenalaRecord> getPrimaryKey() {
		return Keys.TIETEENALA_PKEY;
	}

	/**
	 * {@inheritDoc}
	 */
	@Override
	public List<UniqueKey<TieteenalaRecord>> getKeys() {
		return Arrays.<UniqueKey<TieteenalaRecord>>asList(Keys.TIETEENALA_PKEY);
	}

	/**
	 * {@inheritDoc}
	 */
	@Override
	public List<ForeignKey<TieteenalaRecord, ?>> getReferences() {
		return Arrays.<ForeignKey<TieteenalaRecord, ?>>asList(Keys.TIETEENALA__FK_JULKAISU);
	}

	/**
	 * {@inheritDoc}
	 */
	@Override
	public Tieteenala as(String alias) {
		return new Tieteenala(alias, this);
	}

	/**
	 * Rename this table
	 */
	public Tieteenala rename(String name) {
		return new Tieteenala(name, null);
	}
}

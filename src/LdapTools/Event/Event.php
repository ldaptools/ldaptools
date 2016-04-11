<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Event;

/**
 * An abstract Event class.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
abstract class Event implements EventInterface
{
    /**
     * Occurs before the deletion of an object from LDAP.
     */
    const LDAP_OBJECT_BEFORE_DELETE = 'ldap.object.before_delete';

    /**
     * Occurs after the deletion of an object from LDAP.
     */
    const LDAP_OBJECT_AFTER_DELETE = 'ldap.object.after_delete';

    /**
     * Occurs before the move of an object in LDAP.
     */
    const LDAP_OBJECT_BEFORE_MOVE = 'ldap.object.before_move';

    /**
     * Occurs after the move of an object in LDAP.
     */
    const LDAP_OBJECT_AFTER_MOVE = 'ldap.object.after_move';

    /**
     * Occurs before the modification of an object in LDAP.
     */
    const LDAP_OBJECT_BEFORE_MODIFY = 'ldap.object.before_modify';

    /**
     * Occurs after the modification of an object in LDAP.
     */
    const LDAP_OBJECT_AFTER_MODIFY = 'ldap.object.after_modify';

    /**
     * Occurs before the creation of an object in LDAP.
     */
    const LDAP_OBJECT_BEFORE_CREATE = 'ldap.object.before_create';

    /**
     * Occurs after the creation of an object in LDAP.
     */
    const LDAP_OBJECT_AFTER_CREATE = 'ldap.object.after_create';

    /**
     * Occurs before restoring an object in LDAP.
     */
    const LDAP_OBJECT_BEFORE_RESTORE = 'ldap.object.before_restore';

    /**
     * Occurs after restoring an object in LDAP.
     */
    const LDAP_OBJECT_AFTER_RESTORE = 'ldap.object.after_restore';
    
    /**
     * Occurs when a LDAP object schema is loaded and before it is cached.
     */
    const LDAP_SCHEMA_LOAD = 'ldap.schema.load';

    /**
     * Occurs before a LDAP authentication operation.
     */
    const LDAP_AUTHENTICATION_BEFORE = 'ldap.authentication.before';

    /**
     * Occurs after a LDAP authentication operation.
     */
    const LDAP_AUTHENTICATION_AFTER = 'ldap.authentication.after';

    /**
     * Occurs before a LDAP operation is executed.
     */
    const LDAP_OPERATION_EXECUTE_BEFORE = 'ldap.operation.execute.before';

    /**
     * Occurs after a LDAP operation is executed.
     */
    const LDAP_OPERATION_EXECUTE_AFTER = 'ldap.operation.execute.after';

    /**
     * @var string The event name.
     */
    protected $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name;
    }
}

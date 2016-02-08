<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Utilities;

use LdapTools\Exception\InvalidArgumentException;

/**
 * Represents the time format used in password settings objects in Active Directory that are in I8 format.
 *
 * @see https://technet.microsoft.com/en-us/library/cc753858%28v=ws.10%29.aspx
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ADTimeSpan
{
    /**
     * The time unit for days.
     */
    const DAYS = 'days';

    /**
     * The time unit for hours.
     */
    const HOURS = 'hours';

    /**
     * The time unit for minutes.
     */
    const MINUTES ='minutes';

    /**
     * The time unit for seconds.
     */
    const SECONDS = 'seconds';

    /**
     * The conversion factor table.
     */
    const UNIT = [
        self::DAYS => -864000000000,
        self::HOURS => -36000000000,
        self::MINUTES => -600000000,
        self::SECONDS => -10000000,
    ];

    /**
     * This is how AD represents a time span that never ends for I8 format.
     */
    const NEVER = '-9223372036854775808';

    /**
     * @var int
     */
    protected $days = 0;

    /**
     * @var int
     */
    protected $hours = 0;

    /**
     * @var int
     */
    protected $minutes = 0;

    /**
     * @var int
     */
    protected $seconds = 0;

    /**
     * @var bool
     */
    protected $never = false;

    /**
     * Pass the time in I8 format, which is represented in intervals of -100 nanoseconds.
     *
     * @param string $time
     */
    public function __construct($time = '')
    {
        if (!empty($time)) {
            $this->setValuesFromLdap($time);
        }
    }

    /**
     * Set the days specified for the time span.
     *
     * @param int $days
     * @return $this
     */
    public function setDays($days)
    {
        $this->setAndVerify($days, self::DAYS);

        return $this;
    }

    /**
     * Get the days specified for the time span.
     *
     * @return int
     */
    public function getDays()
    {
        return $this->days;
    }

    /**
     * Set the hours specified for the time span.
     *
     * @param int $hours
     * @return $this
     */
    public function setHours($hours)
    {
        $this->setAndVerify($hours, self::HOURS);

        return $this;
    }

    /**
     * Get the hours specified for the time span.
     *
     * @return int
     */
    public function getHours()
    {
        return $this->hours;
    }

    /**
     * Set the minutes specified for the time span.
     *
     * @param int $minutes
     * @return $this
     */
    public function setMinutes($minutes)
    {
        $this->setAndVerify($minutes, self::MINUTES);

        return $this;
    }

    /**
     * Get the minutes specified for the time span.
     *
     * @return int
     */
    public function getMinutes()
    {
        return $this->minutes;
    }

    /**
     * Set the seconds specified for the time span.
     *
     * @param int $seconds
     * @return $this
     */
    public function setSeconds($seconds)
    {
        $this->setAndVerify($seconds, self::SECONDS);

        return $this;
    }

    /**
     * Get the seconds specified for the time span.
     *
     * @return int
     */
    public function getSeconds()
    {
        return $this->seconds;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setNever($value)
    {
        $this->never = (bool) $value;

        return $this;
    }

    /**
     * Whether the time span value is considered 'Never'. This is a special value in AD that makes the time span
     * indefinite and is represented by a value of -9223372036854775808.
     *
     * @return bool
     */
    public function getNever()
    {
        return $this->never;
    }

    /**
     * Get the value of all the time units in the format that LDAP expects it in.
     *
     * @return string
     */
    public function getLdapValue()
    {
        if ($this->never) {
            $value = self::NEVER;
        } else {
            $total = 0;
            foreach (self::UNIT as $unit => $conversion) {
                $total += $this->$unit * $conversion;
            }
            $value = number_format($total, 0, '.', '');
        }

        return $value;
    }

    /**
     * Get an instance of the class based on a specific time unit as the second parameter.
     *
     * @param int $value The time value.
     * @param string $unit The time unit. One of the constants of this class: DAYS, HOURS, MINUTES, SECONDS.
     * @return $this
     */
    public static function getInstance($value, $unit)
    {
        if (!array_key_exists($unit, self::UNIT)) {
            throw new InvalidArgumentException('Time unit "%s" is not recognized.', $unit);
        }
        $setter = 'set'.ucfirst($unit);

        return (new self)->$setter($value);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->never) {
            $message = 'Never';
        } else {
            $seconds = $this->getLdapValue() / self::UNIT[self::SECONDS];
            $dtBase = new \DateTime('@0');
            $dtSeconds = new \DateTime('@' . $seconds);

            $message = $dtBase->diff($dtSeconds)->format('%a day(s) %h hour(s) %i minute(s) %s second(s)');
        }

        return $message;
    }

    /**
     * Verify the value is an integer and set the correct interval value.
     *
     * @param int $value
     * @param string $interval
     */
    protected function setAndVerify($value, $interval)
    {
        if (!filter_var($value, FILTER_VALIDATE_INT) && $value != '0') {
            throw new InvalidArgumentException(sprintf("The %s should be an integer.", $interval));
        }
        $this->$interval = $value;
    }

    /**
     * Given a time format from LDAP break it down into its individual time units and set them.
     *
     * @param int $time
     */
    protected function setValuesFromLdap($time)
    {
        if ($time == self::NEVER) {
            $this->setNever(true);
        } else {
            $seconds = $time / self::UNIT[self::SECONDS];

            $dtBase = new \DateTime('@0');
            $dtSeconds = new \DateTime('@' . $seconds);

            $this->setDays($dtBase->diff($dtSeconds)->format('%a'));
            $this->setHours($dtBase->diff($dtSeconds)->format('%h'));
            $this->setMinutes($dtBase->diff($dtSeconds)->format('%i'));
            $this->setSeconds($dtBase->diff($dtSeconds)->format('%s'));
        }
    }
}

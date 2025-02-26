<?php
/**
 * MantisBT - A PHP based bugtracking system
 *
 * MantisBT is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * MantisBT is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright 2002, 2024  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 */

/**
 * Class for actions dealing with date periods.
 *
 * This class encapsulates all actions dealing with time intervals. It handles
 * data storage and retrieval, as well as formatting and access.
 *
 * @author Glenn Henshaw <thraxisp@logicaloutcome.ca>
 * @package MantisBT
 */
class Period {
	const TYPE_NONE = 0;
	const TYPE_MONTH_TO_DATE = 1;
	const TYPE_MONTH_PREVIOUS = 2;
	const TYPE_QUARTER_TO_DATE = 3;
	const TYPE_QUARTER_PREVIOUS = 4;
	const TYPE_YEAR_TO_DATE = 5;
	const TYPE_YEAR_PREVIOUS = 6;
	const TYPE_WEEK_TO_DATE = 7;
	const TYPE_WEEK_PREVIOUS = 8;
	const TYPE_WEEK_LAST_TWO = 9;
	const TYPE_ARBITRARY_DATES = 10;

	/**
	 * @var DateTimeImmutable Start Date.
	 */
	private DateTimeImmutable $start;

	/**
	 * @var DateTimeImmutable End Date.
	 */
	private DateTimeImmutable $end;

	/**
	 * @var string Date format
	 */
	private string $format;

	/**
	 * Constructor.
	 *
	 * Set start and end date to Today.
	 */
	function __construct() {
		$this->start = $this->bod();
		$this->end = $this->eod();

		$this->format = config_get( 'normal_date_format' );
	}

	/**
	 * Set dates for a week.
	 *
	 * Weeks start on Monday since PHP 7.0.8, see Changelog section at
	 * {@see https://www.php.net/manual/en/datetime.formats.php}.
	 *
	 * @param DateTimeImmutable $p_when  Reference date.
	 * @param int               $p_weeks Number of weeks.
	 *
	 * @return void
	 */
	function a_week( DateTimeImmutable $p_when, int $p_weeks = 1 ) {
		$this->start = $p_when->modify('monday this week');
		$t_week = $p_weeks == 1 ? 'this' : $p_weeks - 1;
		$t_end = $this->eod( $p_when->modify("sunday $t_week week") );
		$this->end = min( $t_end, new DateTimeImmutable());
	}

	/**
	 * Set dates for this week.
	 *
	 * @return void
	 */
	function this_week() {
		$this->a_week( new DateTimeImmutable() );
	}

	/**
	 * Set dates for last week.
	 *
	 * @param int $p_weeks Number of weeks.
	 *
	 * @return void
	 */
	function last_week( int $p_weeks = 1 ) {
		$t_date = new DateTimeImmutable();
		$this->a_week( $t_date->modify("-1 week"), $p_weeks );
	}

	/**
	 * Set dates for this week to date.
	 *
	 * @return void
	 */
	function week_to_date() {
		$this->this_week();
		$this->end = $this->eod();
	}

	/**
	 * Set dates for a month.
	 *
	 * @param DateTimeImmutable $p_when Reference date.
	 *
	 * @return void
	 */
	function a_month( DateTimeImmutable $p_when ) {
		$this->start = $this->bod( $p_when->modify('first day of this month') );
		$this->end = $this->eod( $p_when->modify('last day of this month') );
	}

	/**
 	 * Set dates for this month.
 	 *
	 * @return void
	 */
	function this_month() {
		$this->a_month( new DateTimeImmutable() );
	}

	/**
	 * Set dates for last month.
	 *
	 * @return void
	 */
	function last_month() {
		$t_date = new DateTimeImmutable();
		$this->a_month( $t_date->modify( "-1 month" ) );
	}

	/**
	 * Set dates for this month to date
	 *
	 * @return void
	 */
	function month_to_date() {
		$this->this_month();
		$this->end = $this->eod();
	}

	/**
	 * Set dates for a quarter.
	 *
	 * @param DateTimeImmutable $p_when Reference date string.
	 *
	 * @return void
	 */
	function a_quarter( DateTimeImmutable $p_when ) {
		# Get first month of quarter
		$t_month = intdiv( $p_when->format('m') - 1, 3 ) * 3 + 1;
		$t_year = $p_when->format('Y');

		$this->start = $this->bod()->setDate( $t_year, $t_month, 1);
		$this->end = $this->eod( $this->start->modify( 'last day of second month' ) );
	}

	/**
	 * Set dates for this quarter.
	 *
	 * @return void
	 */
	function this_quarter() {
		$this->a_quarter( new DateTimeImmutable() );
	}

	/**
	 * Set dates for last quarter.
	 *
	 * @return void
	 */
	function last_quarter() {
		$t_date = new DateTimeImmutable();
		$this->a_quarter( $t_date->modify( '-3 months' ) );
	}

	/**
	 * set dates for this quarter to date
	 *
	 * @return void
	 */
	function quarter_to_date() {
		$this->this_quarter();
		$this->end = $this->eod();
	}

	/**
	 * Set dates for a year.
	 *
	 * @param DateTimeImmutable $p_when Reference date.
	 *
	 * @return void
	 */
	function a_year( DateTimeImmutable $p_when ) {
		$this->start = $this->bod( $p_when->modify('first day of january') );
		$this->end = $this->eod( $p_when->modify('last day of december') );
	}

	/**
	 * Set dates for this year.
	 *
	 * @return void
	 */
	function this_year() {
		$this->a_year( new DateTimeImmutable() );
	}

	/**
	 * Set dates for current year, ending today.
	 *
	 * @return void
	 */
	function year_to_date() {
		$this->this_year();
		$this->end = $this->eod();
	}

	/**
	 * Set dates for last year.
	 *
	 * @return void
	 */
	function last_year() {
		$t_date = new DateTimeImmutable();
		$this->a_year( $t_date->modify( "-1 year" ) );
	}

	/**
	 * Get start date in Unix timestamp format.
	 *
	 * @return int
	 */
	function get_start_timestamp(): int {
		return $this->start->getTimestamp();
	}

	/**
	 * Get end date in Unix timestamp format.
	 *
	 * @return int
	 */
	function get_end_timestamp(): int {
		return $this->end->getTimestamp();
	}

	/**
	 * Get formatted start date.
	 *
	 * @return string
	 */
	function get_start_formatted(): string {
		return $this->type != self::TYPE_NONE ? $this->start->format( $this->format ) : '';
	}

	/**
	 * Get formatted end date.
	 *
	 * @return string
	 */
	function get_end_formatted(): string {
		return $this->type != self::TYPE_NONE ? $this->end->format( $this->format ) : '';
	}

	/**
	 * Get number of days in interval
	 * @return int
	 */
	function get_elapsed_days(): int {
		return $this->start->diff( $this->end )->days;
	}

	/**
	 * Returns HTML markup for a period selector.
	 *
	 * @param string $p_control_name Name of the html control.
	 *
	 * @return string
	 */
	function period_selector( string $p_control_name ): string {
		$t_periods = array(
			self::TYPE_NONE => plugin_lang_get( 'period_none' ),
			self::TYPE_WEEK_TO_DATE => plugin_lang_get( 'period_this_week' ),
			self::TYPE_WEEK_PREVIOUS => plugin_lang_get( 'period_last_week' ),
			self::TYPE_WEEK_LAST_TWO => plugin_lang_get( 'period_two_weeks' ),
			self::TYPE_MONTH_TO_DATE => plugin_lang_get( 'period_this_month' ),
			self::TYPE_MONTH_PREVIOUS => plugin_lang_get( 'period_last_month' ),
			self::TYPE_QUARTER_TO_DATE => plugin_lang_get( 'period_this_quarter' ),
			self::TYPE_QUARTER_PREVIOUS => plugin_lang_get( 'period_last_quarter' ),
			self::TYPE_YEAR_TO_DATE => plugin_lang_get( 'period_year_to_date' ),
			self::TYPE_YEAR_PREVIOUS => plugin_lang_get( 'period_last_year' ),
			self::TYPE_ARBITRARY_DATES => plugin_lang_get( 'period_select' ),
		);

		$t_default = gpc_get_int( $p_control_name, self::TYPE_NONE );
		$t_dropdown = get_dropdown( $t_periods, $p_control_name, $t_default, false, false );
		$t_date_input_pattern = '<label for="%2$s" class="padding-right-4">%1$s</label>'
			. '<input type="text" id="%2$s" name="%2$s" size="14" '
			. 'value="%3$s" class="datetimepicker input-xs" disabled="disabled" '
			. 'data-picker-locale="' . lang_get_current_datetime_locale() . '" '
			. 'data-picker-format="' . config_get( 'datetime_picker_format' ) . '"'
			. '/>'
			. icon_get( 'fa-calendar', 'fa-xlg datetimepicker' );
		$t_from_date = sprintf( $t_date_input_pattern, lang_get( 'from_date' ), 'start_date', $this->get_start_formatted() );
		$t_to_date = sprintf( $t_date_input_pattern, lang_get( 'to_date' ), 'end_date', $this->get_end_formatted() );

		return <<< HTML
			<div id="period_menu">
				$t_dropdown
			</div>
			<br>
			<div id="dates">
				<div class="pull-left padding-right-8">$t_from_date</div>
				<div class="pull-left">$t_to_date</div>
			</div>
		HTML;
	}

	/**
	 * set date based on period selector
	 *
	 * @param string $p_control_name Value representing the name of the html control on the web page.
	 * @param string $p_start_field  Name representing the name of the starting field on the date selector i.e. start_date.
	 * @param string $p_end_field    Name representing the name of the ending field on the date selector i.e. end_date.
	 *
	 * @return void
	 * @TODO consider moving to constructor
	 */
	function set_period_from_selector( string $p_control_name, string $p_start_field = 'start_date', string $p_end_field = 'end_date' ) {
		$t_default = gpc_get_int( $p_control_name, self::TYPE_NONE );
		switch( $t_default ) {
			case self::TYPE_MONTH_TO_DATE:
				$this->month_to_date();
				break;
			case self::TYPE_MONTH_PREVIOUS:
				$this->last_month();
				break;
			case self::TYPE_QUARTER_TO_DATE:
				$this->quarter_to_date();
				break;
			case self::TYPE_QUARTER_PREVIOUS:
				$this->last_quarter();
				break;
			case self::TYPE_YEAR_TO_DATE:
				$this->year_to_date();
				break;
			case self::TYPE_YEAR_PREVIOUS:
				$this->last_year();
				break;
			case self::TYPE_WEEK_TO_DATE:
				$this->week_to_date();
				break;
			case self::TYPE_WEEK_PREVIOUS:
				$this->last_week();
				break;
			case self::TYPE_WEEK_LAST_TWO:
				$this->last_week( 2 );
				break;
			case self::TYPE_ARBITRARY_DATES:
				$t_date_format = config_get( 'normal_date_format' );

				if( $p_start_field != '' ) {
					$t_start_date = gpc_get_string( $p_start_field, '' );
					if( $t_start_date ) {
						$t_start_date = DateTimeImmutable::createFromFormat( $t_date_format, $t_start_date );
						$this->start = $this->bod( $t_start_date ?: null );
					}
				}
				if( $p_end_field != '' ) {
					$t_end_field = gpc_get_string( $p_end_field, '' );
					if( $t_end_field ) {
						$t_end_field = DateTimeImmutable::createFromFormat( $t_date_format, $t_end_field );
						$this->end = min( $this->eod( $t_end_field ?: null ), new DateTimeImmutable() );
					}
				}
				break;
		}
	}

	/**
	 * Sets the time to beginning of day (00:00:00).
	 *
	 * @param DateTimeImmutable|null $p_date
	 *
	 * @return DateTimeImmutable
	 */
	private function bod( ?DateTimeImmutable $p_date = null ): DateTimeImmutable {
		if( $p_date === null ) {
			$p_date = new DateTimeImmutable();
		}
		return $p_date->setTime( 0, 0 );
	}

	/**
	 * Sets the time to end of day (23:59:59).
	 *
	 * @param DateTimeImmutable|null $p_date
	 *
	 * @return DateTimeImmutable
	 */
	private function eod( ?DateTimeImmutable $p_date = null): DateTimeImmutable {
		if( $p_date === null ) {
			return new DateTimeImmutable();
		}
		return $p_date->setTime( 23, 59, 59 );
	}

}

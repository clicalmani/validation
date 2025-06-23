<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

class DateTimeValidator extends Rule
{
    protected static string $argument = 'datetime';

    public function options() : array
    {
        return [
            'format' => [
                'required' => true,
                'type' => 'string',
                'validator' => fn(string $value) => !!preg_match('/^([YmdHis:\s-]+)$/', $value)
            ]
        ];
    }

    public function validate(mixed &$date) : bool
    {
        $format = $this->options['format'];
        $this->cast($date, 'string');
        $this->cast($format, 'string');

        $bindings = [
			'Y' => '[0-9]{4}',
			'm' => '[0-9]{2}',
			'd' => '[0-9]{2}',
			'H' => '[0-9]{2}',
			'i' => '[0-9]{2}',
			's' => '[0-9]{2}'
		];

		foreach ($bindings as $k => $v) {
			$format = str_replace($k, $v, $format);
		}
		
		return !! @ preg_match('/^' . $format . '$/i', $date);
    }
}

<?php

declare (strict_types=1);
namespace Json_Schema;

/**
 * @method static DraftIdentifiers DRAFT_3()
 * @method static DraftIdentifiers DRAFT_4()
 * @method static DraftIdentifiers DRAFT_6()
 * @method static DraftIdentifiers DRAFT_7()
 * @method static DraftIdentifiers DRAFT_2019_09()
 * @method static DraftIdentifiers DRAFT_2020_12()
 */
class Draft_Identifiers extends Enum
{
    public const DRAFT_3 = 'http://json-schema.org/draft-03/schema#';
    public const DRAFT_4 = 'http://json-schema.org/draft-04/schema#';
    public const DRAFT_6 = 'http://json-schema.org/draft-06/schema#';
    public const DRAFT_7 = 'http://json-schema.org/draft-07/schema#';
    public const DRAFT_2019_09 = 'https://json-schema.org/draft/2019-09/schema';
    public const DRAFT_2020_12 = 'https://json-schema.org/draft/2020-12/schema';
    /** @var array<DraftIdentifiers::DRAFT_*, string> */
    private const MAPPING = [self::DRAFT_3 => 'draft03', self::DRAFT_4 => 'draft04', self::DRAFT_6 => 'draft06', self::DRAFT_7 => 'draft07', self::DRAFT_2019_09 => 'draft2019-09', self::DRAFT_2020_12 => 'draft2020-12'];
    private const FALLBACK_MAPPING = ['draft3' => self::DRAFT_3, 'draft4' => self::DRAFT_4, 'draft6' => self::DRAFT_6, 'draft7' => self::DRAFT_7];
    public function to_constraint_name(): string
    {
        return self::MAPPING[$this->get_value()];
    }
    public static function from_constraint_name(string $name): Draft_Identifiers
    {
        $reverse_map = array_flip(self::MAPPING);
        if (!array_key_exists($name, $reverse_map)) {
            if (array_key_exists($name, self::FALLBACK_MAPPING)) {
                return Draft_Identifiers::by_value(self::FALLBACK_MAPPING[$name]);
            }
            throw new \InvalidArgumentException("{$name} is not a valid constraint name.");
        }
        return Draft_Identifiers::by_value($reverse_map[$name]);
    }
    public function without_fragment(): string
    {
        return rtrim($this->get_value(), '#');
    }
}
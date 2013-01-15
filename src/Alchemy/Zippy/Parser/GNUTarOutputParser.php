<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Zippy\Parser;

use Alchemy\Zippy\Member;
#([ldrwx-]{10})\s+([a-z][-a-z0-9]*)/([a-z][-a-z0-9]*)\s+(\d*)\s+(([0-9]{4})-(1[0-2]|0[1-9])-(3[0-1]|0[1-9]|[1-2][0-9]))\s+((2[0-3]|[0-1][0-9]):([0-5][0-9]))\s+(.*)#
/**
 * This class is responsable of parsing GNUTar command line output
 */
class GNUTarOutputParser implements ParserInterface
{
    const PERMISSIONS   = "([ldrwx-]+)";
    const OWNER         = "([a-z][-a-z0-9]*)";
    const GROUP         = "([a-z][-a-z0-9]*)";
    const FILESIZE      = "(\d*)";
    const ISO_DATE      = "([0-9]+-[0-9]+-[0-9]+\s+[0-9]+:[0-9]+)";
    const FILENAME      = "(.*)";

    /**
     * @inheritdoc
     */
    public function parseFileListing($output)
    {
        $lines = array_values(array_filter(explode("\n", $output)));
        $members = array();

        foreach ($lines as $line) {
            $matches = array();
            
            // -rw-r--r-- gray/staff    62373 2006-06-09 12:06 apple
            if (!preg_match_all("#".
                self::PERMISSIONS       . "\s+" . // match (-rw-r--r--)
                self::OWNER             . "/"   . // match (gray)
                self::GROUP             . "\s+" . // match (staff)
                self::FILESIZE          . "\s+" . // match (62373)
                self::ISO_DATE          . "\s+" . // match (2006-06-09 12:06)
                self::FILENAME          .         // match (apple)
                "#",
                $line, $matches, PREG_SET_ORDER
            )) {
                continue;
            }

            $chunks = array_shift($matches);

            if (7 !== count($chunks)) {
                continue;
            }

            $members[] = new Member(
                $chunks[6],
                $chunks[4],
                \DateTime::createFromFormat("Y-m-d H:i", $chunks[5]),
                'd' === $chunks[1][0]
            );
        }

        return $members;
    }

    /**
     * @inheritdoc
     */
    public function parseVersion($output)
    {
        $chuncks = explode(' ', $output, 3);

        if (2 > count($chuncks)) {
            return null;
        }

        list($name, $version) = $chuncks;

        return $version;
    }
}

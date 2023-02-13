<?php

namespace MysqlToSqlite;

class OutputFilter
{
    public function filter($output)
    {
        // bypass insecure password warning
        if (str_contains($output, 'password on the command line')) {
            return null;
        }

        // bypass standard "memory" output... not sure why that's a thing
        if (str_contains($output, 'memory')) {
            return null;
        }

        if (str_contains($output, 'Expression #6 of SELECT list is not in GROUP BY clause and contains nonaggregated column \'information_schema.FILES.EXTRA')) {
            return null;
        }

        return $output;
    }
}

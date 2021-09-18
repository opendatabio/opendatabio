<?php

return array (
  'directories' => 
  array (
    0 => '/var/www/html/app/Models',
  ),
  'ignore' => 
  array (
  ),
  'whitelist' => 
  array (
    0 => 'App\\Models\\Dataset',
    1 => 'App\\Models\\Measurement',
    2 => 'App\\Models\\Individual',
    3 => 'App\\Models\\Voucher',
    4 => 'App\\Models\\Media',
    5 => 'App\\Models\\User',
    6 => 'App\\Models\\Project',
  ),
  'recursive' => false,
  'use_db_schema' => true,
  'use_column_types' => true,
  'table' => 
  array (
    'header_background_color' => '#D5EDF6',
    'header_font_color' => '#333333',
    'header_font_size' => 12,
    'row_background_color' => '#F0F0F0',
    'row_font_color' => '#333333',
    'row_font_size' => 11,
  ),
  'graph' => 
  array (
    'style' => 'filled',
    'bgcolor' => '#FFFFFF',
    'labelloc' => 't',
    'labelfloat' => true,
    'concentrate' => false,
    'splines' => 'spline',
    'overlap' => false,
    'rankdir' => 'LR',
    'ranksep' => 2,
    'nodesep' => 1,
    'esep' => false,
    'rotate' => 0,
    'fontname' => 'Helvetica Neue',
    'dpi' => 150,
  ),
  'node' => 
  array (
    'margin' => 0,
    'shape' => 'rectangle',
    'fontname' => 'Helvetica Neue',
    'fontsize' => 11,
  ),
  'edge' => 
  array (
    'color' => '#003049',
    'fontcolor' => '#003049',
    'penwidth' => 1.5,
    'fontname' => 'Helvetica Neue',
    'fontsize' => 12,
  ),
  'relations' => 
  array (
    'HasOne' => 
    array (
      'dir' => 'both',
      'color' => '#FFCC00',
      'arrowhead' => 'tee',
      'arrowtail' => 'none',
    ),
    'BelongsTo' => 
    array (
      'dir' => 'both',
      'color' => '#7B0099',
      'fontcolor' => '#7B0099',
      'arrowhead' => 'normal',
      'arrowtail' => 'dot',
    ),
    'BelongsToMany' => 
    array (
      'dir' => 'both',
      'color' => '#FB9902',
      'fontcolor' => '#FB9902',
      'arrowhead' => 'crow',
      'arrowtail' => 'crow',
    ),
    'HasMany' => 
    array (
      'dir' => 'both',
      'color' => '#4285F4',
      'fontcolor' => '#4285F4',
      'arrowhead' => 'crow',
      'arrowtail' => 'dot',
    ),
    'MorphMany' => 
    array (
      'dir' => 'both',
      'color' => '#EA4335',
      'fontcolor' => '#EA4335',
      'arrowhead' => 'crow',
      'arrowtail' => 'dot',
    ),
    'MorphTo' => 
    array (
      'dir' => 'both',
      'color' => '#EA4335',
      'fontcolor' => '#EA4335',
      'arrowhead' => 'normal',
      'arrowtail' => 'dot',
      'style' => 'dotted',
    ),
    'HasManyThrough' => 
    array (
      'dir' => 'both',
      'color' => '#A4C639',
      'fontcolor' => '#A4C639',
      'arrowhead' => 'crow',
      'arrowtail' => 'dot',
      'style' => 'dashed',
    ),
    'HasOneThrough' => 
    array (
      'dir' => 'both',
      'color' => '#FFCC00',
      'fontcolor' => '#FFCC00',
      'arrowhead' => 'normal',
      'arrowtail' => 'dot',
      'style' => 'dashed',
    ),
  ),
);
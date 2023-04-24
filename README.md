# ChangeLog Writer

ChangeLog Writer - это инструмент, который позволяет автоматически генерировать файл Changelog в вашем проекте.

## Требования к окружению

Для работы ChangeLog Writer требуется PHP версии 7.3.0 и выше.

Файл CHANGELOG.md в корне приложения

## Установка

Для начала работы с ChangeLog Writer выполните следующие шаги:

1. Установите пакет с помощью Composer:

   ```bash
   composer require hiddenpathz/changelog-writer
   ```

2. Добавьте в свой ENV файл переменную с ссылкой на ваш репозиторий:

   ```bash
   REPOSITORY_LINK=http://gitlab.some.ru
   ```

   Также добавьте эту переменную в контейнер в файле docker-compose.yml:

   ```yaml
   environment:
     REPOSITORY_LINK: '${REPOSITORY_LINK}'
   ```

3. Ваша консольная команда для запуска ChangeLog Writer выглядит следующим образом:

   ```bash
   hiddenpatz/changelogWriter/bin http://gitlab.some.ru
   ```

4. Добавьте следующий код в раздел `scripts` вашего файла `composer.json`. Данный код позволит вызывать команду `changelog-write` через Composer:

   ```json
   "scripts": {
       "changelog-write": [ "hiddenpathz/changelogWriter/bin http://gitlab.some.ru" ]
   }
   ```

   Далее вы сможете использовать эту команду, вызывая ее следующим образом:

   ```bash
   composer changelog-write
   ```

## Использование

Для использования ChangeLog Writer достаточно вызвать его через терминал и убедиться, что версия вашего проекта поднимается.

* Если вы работаете на сайте, то выполните следующую команду:

   ```bash
   hiddenpathz/changelogWriter/bin http://gitlab.some.ru
   ```

После вызова скрипта файл ChangeLog будет автоматически сгенерирован, с отображением всех изменений.

Удостоверьтесь, что ваш коммит содержит ключевые слова в сообщении, для того чтобы они были отображены в файле Changelog.

| Ключ     | Запись      |
|----------|-------------|
| feat     | Реализовано |
| refactor | Изменено    |
| fix      | Исправлено  |
| remove   | Удалено     |

## Пример коммита который попадет в changelog.

   ```bash
   refactor: Rewrite order method
   ```

## Пример результата

```md
# История изменений

## [ [1.1.0](https://gitlab.some.ru/your.repo.ru//-/tags/1.1.0) ] - 01.01.2023
- Реализовано:
  - Установлена версия РНР до 8.2
  - Добавлен пакет Redis
- Изменено:
  - Переписан метод получения заказа
```

## Лицензия

ChangeLog Writer выпущен под лицензией MIT. Подробную информацию можно найти в файле LICENSE в корневой директории проекта.
<?php
namespace k7zz\humhub\bbb\helpers;

class GreenlightRooms
{
    private $_all = [
        "93r-bw4-bxb-t2r" => [
            "id" => "kjng5upvxnoc0wlbenpeupcpvfgbbv47im7phe9o",
            "name" => "Raum von Britta Freudenberg",
        ],
        "n2o-mvz-dtm-ewc" => [
            "id" => "go6oubfyzqylmdlwbnancjqsskqjugrglznoienh",
            "name" => "Raum von Claudia Wiewel",
        ],
        "2ln-zmv-2xp-ywf" => [
            "id" => "yezzs0ej3dpmkajxhyr1rasgjfijo8jwlpovg509",
            "name" => "Lobby",
        ],
        "ysa-bln-ggl-q7r" => [
            "id" => "st6oifsusesn3emejjw3sndtwt2r4wfctxymlsfa",
            "name" => "Lounge",
        ],
        "ey0-fc3-iam-4be" => [
            "id" => "v07ht4pnxmqoowk4qreoscxsdbfwjwuswvzu8dkw",
            "name" => "Raum von Christian Koehler",
        ],
        "jhu-wey-tor-1zg" => [
            "id" => "dlfwu9o0t19tngktco6qdaswclnodesr1mlgum5q",
            "name" => "Raum von Stefan Pump",
        ],
        "wk4-jns-ril-tui" => [
            "id" => "6gz04q8dqj06zaznkpqvj1kjv1vqyp9zlbkmodrp",
            "name" => "Raum von Carola Herbst",
        ],
        "kk1-6kt-qby-rls" => [
            "id" => "ncvbanspf1ens95xxsrlsblwnzopmeooyqgl9pjn",
            "name" => "Online-Seminare",
        ],
        "ap0-mi5-h3c-dum" => [
            "id" => "uku9ihnqflwzt00rhgmqefk4cdeevhmjdqarn2rs",
            "name" => "Raum von Niels Heinemann",
        ],
        "nob-6ug-vza-4sf" => [
            "id" => "zgxowwvxlxuzx09e3fwof5akx6noug2cx0gh4j3g",
            "name" => "Zukunftsraum kommunale Demografiegestaltung",
        ],
        "srf-el2-lrv-mqr" => [
            "id" => "p1dct3djfzvqd70xtynafmiv08ylqbzqvsf5amry",
            "name" => "Raum von Bärbel Henkenjohann / Zukunftsraum",
        ],
        "p8w-zue-myr-2la" => [
            "id" => "3t7etwdbxhapzx9s6m3ubalu0z5szmga7jyj7fkq",
            "name" => "Raum von Susanne Macaluso",
        ],
        "dq4-wvt-6wg-xvi" => [
            "id" => "sendk94gazcjo4v3epf64bphxc6nbd8dldnc7krq",
            "name" => "Raum von Nuriel Kilicli",
        ],
        "kt9-aa9-k51-sq3" => [
            "id" => "ui8lalbixsb5avb9wblgsv2zqk6n8wobp7lfqzx2",
            "name" => "interner Austausch Beraterinnen und Berater",
        ],
        "ng2-w4w-ihe-k9c" => [
            "id" => "odpkrh9advnzyadt3z9rsefisddxm1loyuvzazda",
            "name" => "Raum von Nielsi Heineman",
        ],
        "i01-77a-kfk-gnb" => [
            "id" => "nwugvmcpv3azqovple0dgewpt8pu31criqkfrxpo",
            "name" => "Zukunftsraum kommunale Demografiegestaltung",
        ],
        "zjl-fax-46x-4w5" => [
            "id" => "xhrry1rd4fngiv0i5y4gj2noucicrukk27qjbfrg",
            "name" => "Teststreifen",
        ],
        "2p7-ch7-rhb-w7y" => [
            "id" => "uud7p3ga0y8qxtj4phkvmdqll1xaiq5uzjtic3lk",
            "name" => "Raum von Geschäftsstelle Zukunftsraum Demografie",
        ],
        "uen-z7u-1s5-ssv" => [
            "id" => "sm008pmpgytzpqbtea84u66cjv5tpx1znmbjugmt",
            "name" => "Raum von Karin Lühmann",
        ],
        "e05-2rt-pp5-veo" => [
            "id" => "dgdlllawjtlcyllbnsruwygumd0qr3bqc9qungxy",
            "name" => "Raum von Nille H",
        ],
        "m1a-8rd-3mt-liu" => [
            "id" => "7jg9pkkkvm2crc94ug1wzsxkp09ldfcpljfobo0i",
            "name" => "Sprechstunde",
        ],
        "uiq-50q-rer-vjg" => [
            "id" => "hhe1j6zcu4kuh3qc6xeiytgdyt8zueievrcdnkk8",
            "name" => "Raum von Robert van Iterson",
        ],
        "ksx-0dj-xip-g8n" => [
            "id" => "dwlms7nvnt0zj7gjjtxdxvcukk82euxxnr0i63va",
            "name" => "Austausch",
        ],
        "hrp-rzx-fzp-ynk" => [
            "id" => "szgoatwb8b5tsvmfnizp1ekbndbkjd4mjybdxbf9",
            "name" => "teste-den-join",
        ]
    ];

    static $list = [
        "kk1-6kt-qby-rls" => [
            "id" => "ncvbanspf1ens95xxsrlsblwnzopmeooyqgl9pjn",
            "name" => "Online-Seminare",
            "reserved" => true,
        ],
        "nob-6ug-vza-4sf" => [
            "id" => "zgxowwvxlxuzx09e3fwof5akx6noug2cx0gh4j3g",
            "name" => "Zukunftsraum kommunale Demografiegestaltung",
        ],
        "kt9-aa9-k51-sq3" => [
            "id" => "ui8lalbixsb5avb9wblgsv2zqk6n8wobp7lfqzx2",
            "name" => "interner Austausch Beraterinnen und Berater",
            "group" => "Beraterinnen und Berater",
        ],
        "m1a-8rd-3mt-liu" => [
            "id" => "7jg9pkkkvm2crc94ug1wzsxkp09ldfcpljfobo0i",
            "name" => "Sprechstunde",
        ],
        "ksx-0dj-xip-g8n" => [
            "id" => "dwlms7nvnt0zj7gjjtxdxvcukk82euxxnr0i63va",
            "name" => "Austausch",
        ],
    ];
}

/*
kjng5upvxnoc0wlbenpeupcpvfgbbv47im7phe9o | 93r-bw4-bxb-t2r | Raum von Britta Freudenberg
go6oubfyzqylmdlwbnancjqsskqjugrglznoienh | n2o-mvz-dtm-ewc | Raum von Claudia Wiewel
yezzs0ej3dpmkajxhyr1rasgjfijo8jwlpovg509 | 2ln-zmv-2xp-ywf | Lobby
st6oifsusesn3emejjw3sndtwt2r4wfctxymlsfa | ysa-bln-ggl-q7r | Lounge
v07ht4pnxmqoowk4qreoscxsdbfwjwuswvzu8dkw | ey0-fc3-iam-4be | Raum von Christian Koehler
dlfwu9o0t19tngktco6qdaswclnodesr1mlgum5q | jhu-wey-tor-1zg | Raum von Stefan Pump
6gz04q8dqj06zaznkpqvj1kjv1vqyp9zlbkmodrp | wk4-jns-ril-tui | Raum von Carola Herbst
ncvbanspf1ens95xxsrlsblwnzopmeooyqgl9pjn | kk1-6kt-qby-rls | Online-Seminare
uku9ihnqflwzt00rhgmqefk4cdeevhmjdqarn2rs | ap0-mi5-h3c-dum | Raum von Niels Heinemann
zgxowwvxlxuzx09e3fwof5akx6noug2cx0gh4j3g | nob-6ug-vza-4sf | Zukunftsraum kommunale Demografiegestaltung
p1dct3djfzvqd70xtynafmiv08ylqbzqvsf5amry | srf-el2-lrv-mqr | Raum von Bärbel Henkenjohann / Zukunftsraum
3t7etwdbxhapzx9s6m3ubalu0z5szmga7jyj7fkq | p8w-zue-myr-2la | Raum von Susanne Macaluso
sendk94gazcjo4v3epf64bphxc6nbd8dldnc7krq | dq4-wvt-6wg-xvi | Raum von Nuriel Kilicli
ui8lalbixsb5avb9wblgsv2zqk6n8wobp7lfqzx2 | kt9-aa9-k51-sq3 | interner Austausch Beraterinnen und Berater
odpkrh9advnzyadt3z9rsefisddxm1loyuvzazda | ng2-w4w-ihe-k9c | Raum von Nielsi Heineman
nwugvmcpv3azqovple0dgewpt8pu31criqkfrxpo | i01-77a-kfk-gnb | Zukunftsraum kommunale Demografiegestaltung
xhrry1rd4fngiv0i5y4gj2noucicrukk27qjbfrg | zjl-fax-46x-4w5 | Teststreifen
uud7p3ga0y8qxtj4phkvmdqll1xaiq5uzjtic3lk | 2p7-ch7-rhb-w7y | Raum von Geschäftsstelle Zukunftsraum Demografie
sm008pmpgytzpqbtea84u66cjv5tpx1znmbjugmt | uen-z7u-1s5-ssv | Raum von Karin Lühmann
dgdlllawjtlcyllbnsruwygumd0qr3bqc9qungxy | e05-2rt-pp5-veo | Raum von Nille H
7jg9pkkkvm2crc94ug1wzsxkp09ldfcpljfobo0i | m1a-8rd-3mt-liu | Sprechstunde
hhe1j6zcu4kuh3qc6xeiytgdyt8zueievrcdnkk8 | uiq-50q-rer-vjg | Raum von Robert van Iterson
dwlms7nvnt0zj7gjjtxdxvcukk82euxxnr0i63va | ksx-0dj-xip-g8n | Austausch
szgoatwb8b5tsvmfnizp1ekbndbkjd4mjybdxbf9 | hrp-rzx-fzp-ynk | teste-den-join
*/
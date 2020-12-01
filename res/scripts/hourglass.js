
/*
                       Dead end
                           X
                           N
                      ┌─────────┐
                      │  ▓▓▓▓▓▓▓│
                      │░░▒ ♦ ▒░░│
Forest outskirts ◄- W │░░▒ ☺ ▒░░│ E -► Forest outskirts
                      │░░▒♦ ▒▒░░│
                      │░░░░░░░▒▒│
                      └─────────┘
                           S
                           ▼
                    Forest outskirts

*/



/*

    places:{
        north:{
           items: 2,
           biome: "DESERT",
           name: "Desert town",
           is_explored: true,
           is_dead_end: false,
        },
        here:{
            
        }
        ...        
    }
*/

const MAX_NAME_WIDTH = 15;
const SIDE_BAND_WIDTH = 2;
const BASE_CHARACTER = " ";
const ITEM_COLOR = "#ffff00";
const UNK_COLOR = "#d9d9d9";
const ITEM_CHARACTER = "♦";

function getUpdatedHourglass(places){
    const borders = makeBorders();
        
    const topography = makeMap(borders, places);
    const entityMap = drawEntities(topography, places);
    const marked = addLabels(entityMap, places);
    
    const finl = marked.map((line)=> line.join("")).join("\n");
    return finl;    
}

function getEditorHourglass(){
    const offset = " ".repeat(MAX_NAME_WIDTH+6);
    const borders = makeBorders().map((line)=> offset+line.join(""));
    const mid = Math.floor(borders.length/2);
    
    borders[mid-2] = offset+"│▒▒▒▒▒▒▒▒▒│";
    borders[mid] = offset+"│EDIT MODE│";
    borders[mid+2] = offset+"│▒▒▒▒▒▒▒▒▒│";
    
    return "\n\n\n"+borders.join("\n");
}

function makeBorders(){
    return ("┌─────────┐\n" 
          + "│         │\n"
          + "│         │\n"
          + "│         │\n"
          + "│         │\n"
          + "│         │\n"
          + "└─────────┘").split("\n").map((line)=>line.split(""));
}

function makeMap(borders, places){

    let splitted = borders;
    const northHasWest = places.north?.name?.length > places.north?.name?.length;
    const southHasWest = places.south?.name?.length > places.west?.name?.length;
    const northHasEast = places.north?.name?.length > places.east?.name?.length;
    const southHasEast = places.south?.name?.length > places.south?.name?.length;    

    const unknownCharStr = "?".repeat(60);
    
    let str, color;
    
    // North band
    
    str = places.north.is_explored ? getPlaceString(biomeContents[places.north.biome].character, places.north.name) : unknownCharStr;
    let northBand = splitted[1];
    color = places.north.is_explored ? biomeContents[places.north.biome].color : null;
    for(i in northBand){
        const ci = parseInt(i);
        if (ci == 0) continue;
        if (ci == northBand.length-1) continue;
        if (ci < 3 && !northHasWest) continue;
        if (ci == Math.floor(northBand.length/2) && !places.north.is_dead_end) continue;
        if (ci > 8 && !northHasEast){
            break;   
        }
    
        northBand[ci] = places.north.is_explored ? (str[ci] === " " ? " " : "<span style='color:"+color+";'>"+str[ci]+"</span>") : unknownCharStr[ci];
    }
    
    // South band
    str = places.south.is_explored ? getPlaceString(biomeContents[places.south.biome].character, places.south.name) : unknownCharStr;
    let southBand = splitted[splitted.length-2];
    color = places.south.is_explored ? biomeContents[places.south.biome].color : null;
    for(i in southBand){
        const ci = parseInt(i);
        if (ci == 0) continue;
        if (ci == southBand.length-1) continue;
        if (ci < 3 && !southHasWest) continue;
        if (ci == Math.floor(southBand.length/2)  && !places.south.is_dead_end) continue;
        if (ci > 8 && !southHasEast){
            break;
        }
        
        southBand[ci] = places.south.is_explored ? (str[ci] === " " ? " " : "<span style='color:"+color+";'>"+str[ci]+"</span>") : unknownCharStr[ci];
    }
    
    // West columns
    str = places.west.is_explored ? getPlaceString(biomeContents[places.west.biome].character, places.west.name) : unknownCharStr;
    color = places.west.is_explored ? biomeContents[places.west.biome].color : null;
    for (lineIndex in splitted){
        const line = splitted[lineIndex];
        if (lineIndex == 0) continue;
        if (lineIndex == splitted.length-1) continue;
        if (lineIndex == 1 && northHasWest) continue;
        if (lineIndex == splitted.length-2 && southHasWest) continue;
        if (lineIndex == Math.floor(splitted.length/2) && !places.west.is_dead_end) continue;
        
        for(let i = 0; i < SIDE_BAND_WIDTH; i++){
            splitted[lineIndex][1+i] = places.west.is_explored ? "<span style='color:"+color+";'>"+str[(lineIndex+i)%str.length]+"</span>" : unknownCharStr[i];
        }
    }
        
    // East columns
    str = places.east.is_explored ? getPlaceString(biomeContents[places.east.biome].character, places.east.name) : unknownCharStr;
    color = places.east.is_explored ? biomeContents[places.east.biome].color : null;
    for (lineIndex in splitted){
        const line = splitted[lineIndex];
        if (lineIndex == 0) continue;
        if (lineIndex == splitted.length-1) continue;
        if (lineIndex == 1 && northHasEast) continue;
        if (lineIndex == splitted.length-2 && southHasEast) continue;
        if (lineIndex == Math.floor(splitted.length/2) && !places.east.is_dead_end) continue;
        
        for(let i = 0; i < SIDE_BAND_WIDTH; i++){
            splitted[lineIndex][splitted[lineIndex].length-2-i] = places.east.is_explored ? "<span style='color:"+color+";'>"+str[(lineIndex+i)%str.length]+"</span>" : unknownCharStr[i];
        }
    }
    
    
    // Central
    str = getPlaceString(biomeContents[places.here.biome].character, places.here.name);
    color = biomeContents[places.here.biome].color;
    for(y in splitted){
        for(_ in splitted[y]){
            const x = parseInt(_);
            
            if (x < 3) continue;
            if (x > splitted[y].length-2-SIDE_BAND_WIDTH) continue;
            if (y < 2) continue;
            if (y > splitted.length-3) continue;
            if (y == Math.floor(splitted.length/2)) continue;
            if (x == Math.floor(splitted[y].length/2)) continue;
        
            splitted[y][x] = "<span style='color:"+color+";'>"+str[(x*y)%str.length]+"</span>";
        }
    }
    
    return splitted;
}

function drawEntities(map, places){
    
    const yMidPoint = Math.floor(map.length/2);
    const xMidPoint = Math.floor(map[0].length/2);

    map[yMidPoint][xMidPoint] = "<span style='color:white;'>☺</span>";
    
    for(let i = 0; i < places.here.items; i++){
        const y = yMidPoint + (randomBool(hashCode(places.here.name)+i) ? -1 : 1);
        const x = xMidPoint + (randomBool((hashCode(places.here.name)+i)^2) ? -1 : 1);
        map[y][x] = "<span style='color:"+ITEM_COLOR+";'>"+ITEM_CHARACTER+"</span>";
    }
    
    return map;
}

function addLabels(map, places){
    const splitMap = map;
    const mapWidth = splitMap[0].length; 
    const mapHeight = splitMap.length;
    
    const leftOffset = MAX_NAME_WIDTH + 6; // "<-" plus "W" plus three spaces = 6
    
    const elipsedLeftLines = ellipsis(places.west.name, MAX_NAME_WIDTH).split(" ").slice(0, 3);
    const elipsedRightLines = ellipsis(places.east.name, MAX_NAME_WIDTH).split(" ").slice(0, 3);
    const elipsedTop = ellipsis(places.north.name, MAX_NAME_WIDTH);
    const elipsedBottom = ellipsis(places.south.name, MAX_NAME_WIDTH);
    
    const midOffset = Math.floor(leftOffset + mapWidth/2);
    const topLeftOffset = Math.ceil(midOffset - elipsedTop.length/2);
    const bottomLeftOffset = Math.ceil(midOffset - elipsedBottom.length/2);
    
    let newMap = [];
    newMap.push(" ".repeat(topLeftOffset) + elipsedTop);
    newMap.push(" ".repeat(midOffset) + "▲");
    newMap.push(" ".repeat(midOffset) + "N");
    
    for(let i = 0; i < mapHeight; i++){
        if (i > mapHeight/2-1){
            const midPoint = Math.floor(mapHeight/2);
            if (i === midPoint){
                newMap.push(" ".repeat(MAX_NAME_WIDTH - elipsedLeftLines[0].length) +  elipsedLeftLines[0] + " ◄- W " + splitMap[i].join("") + " E -► " + elipsedRightLines[0]);
            }
            else{
                const rightLine = elipsedRightLines[i-midPoint] == undefined ? "" : elipsedRightLines[i-midPoint];

                if (elipsedLeftLines[i-midPoint] != undefined){
                    newMap.push(" ".repeat(MAX_NAME_WIDTH - elipsedLeftLines[i-midPoint].length) + elipsedLeftLines[i-midPoint] + " ".repeat(6) + splitMap[i].join("") + " ".repeat(6) + rightLine);
                }
                else{
                    newMap.push(" ".repeat(leftOffset) + splitMap[i].join(""));
                }
            }
        }
        else{        
            newMap.push(" ".repeat(leftOffset) + splitMap[i].join(""));
        }
    }
    
    newMap.push(" ".repeat(midOffset) + "S");    
    newMap.push(" ".repeat(midOffset) + "▼");
    newMap.push(" ".repeat(bottomLeftOffset) + elipsedBottom);
    
    return newMap.map(line => line.split(""));
}

function ellipsis (input, lngth) {
    return input.length > lngth ? `${input.substring(0, lngth)}...` : input;
}

function getPlaceString(biomeChar, placeName){
    let str = "";    
    for (let i = 0; i < 50; i++){
        str += randomBool(hashCode(placeName)^(i+1) * (i+1)) ? BASE_CHARACTER : biomeChar[(hashCode(placeName[0])+i^2)%biomeChar.length];
        //str += biomeChar;
    }
    return str;
}

function hashCode(s) {
  var h = 0, l = s.length, i = 0;
  if ( l > 0 )
    while (i < l)
      h = (h << 5) - h + s.charCodeAt(i++) | 0;
  return h;
};

function randomBool(seed) { 
    seed = Math.abs((seed * 9301 + 49297) % 233280);
    var rnd = seed/ 233280;
    return rnd > 0.5;
}